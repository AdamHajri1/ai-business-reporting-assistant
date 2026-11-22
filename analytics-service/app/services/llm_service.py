import json
import os
import re
from pathlib import Path
from typing import Any

import httpx

PROJECT_ROOT = Path(__file__).resolve().parents[3]
ENV_FILE = PROJECT_ROOT / ".env"

MONTH_NAMES = (
    r"January|February|March|April|May|June|July|August|September|October|November|December|"
    r"Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec|"
    r"janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre"
)
DATE_PATTERN = re.compile(
    rf"\b\d{{4}}-\d{{2}}(?:-\d{{2}})?\b|"
    rf"\b(?:{MONTH_NAMES})\s+\d{{1,2}}(?:,\s*\d{{4}})?\b|"
    rf"\b\d{{1,2}}\s+(?:{MONTH_NAMES})(?:\s+\d{{4}})?\b",
    re.IGNORECASE,
)
# Matches numbers like $1,250.50, -12.5%, +50.00%, 4, 160.0
NUMBER_PATTERN = re.compile(r"(?<![A-Za-z0-9_])[\+\-]?\$?\d{1,3}(?:,\d{3})*(?:\.\d+)?%?(?![A-Za-z0-9_])")


def _load_env_file() -> dict[str, str]:
    values: dict[str, str] = {}
    if not ENV_FILE.is_file():
        return values

    for raw_line in ENV_FILE.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, val = line.split("=", 1)
        values[key.strip()] = val.strip().strip('"').strip("'")
    return values


def get_llm_config() -> dict[str, str]:
    file_env = _load_env_file()

    def _get(key: str, default: str = "") -> str:
        return os.environ.get(key) or file_env.get(key) or default

    return {
        "provider": _get("LLM_PROVIDER", "none").strip().lower(),
        "api_key": _get("LLM_API_KEY", "").strip(),
        "model": _get("LLM_MODEL", "gemini-3.8-flash").strip(),
        "base_url": _get("LLM_BASE_URL", "").strip(),
    }


def _collect_verified_numbers(analytics: dict[str, Any]) -> set[float]:
    verified: set[float] = set()

    def _walk(node: Any) -> None:
        if isinstance(node, bool) or node is None:
            return
        if isinstance(node, (int, float)):
            val = float(node)
            verified.add(round(val, 2))
            verified.add(round(abs(val), 2))
            return
        if isinstance(node, dict):
            for v in node.values():
                _walk(v)
        elif isinstance(node, list):
            for item in node:
                _walk(item)

    _walk(analytics)
    return verified


def extract_numbers_from_text(text: str) -> list[tuple[str, float]]:
    # Strip YYYY-MM-DD / YYYY-MM dates first
    cleaned = DATE_PATTERN.sub(" ", text)
    found: list[tuple[str, float]] = []
    for match in NUMBER_PATTERN.finditer(cleaned):
        raw = match.group(0)
        normalized = raw.replace("$", "").replace(",", "").replace("%", "").strip()
        try:
            found.append((raw, float(normalized)))
        except ValueError:
            continue
    return found


def verify_numbers_against_metrics(
    summary_payload: dict[str, Any],
    analytics: dict[str, Any],
    tolerance: float = 0.02,
) -> dict[str, Any]:
    verified_pool = _collect_verified_numbers(analytics)

    text_chunks: list[str] = [
        str(summary_payload.get("headline", "")),
        str(summary_payload.get("executive_summary", "")),
    ]
    for field in ("facts", "interpretations", "recommendations"):
        items = summary_payload.get(field, [])
        if isinstance(items, list):
            text_chunks.extend(str(item) for item in items)

    extracted = extract_numbers_from_text("\n".join(text_chunks))
    unverified: list[str] = []

    for raw, num in extracted:
        matched = any(abs(num - target) <= tolerance or abs(abs(num) - target) <= tolerance for target in verified_pool)
        if not matched:
            unverified.append(raw)

    return {
        "passed": len(unverified) == 0,
        "checked_count": len(extracted),
        "unverified_numbers": unverified,
    }


def build_grounding_prompt(analytics: dict[str, Any], locale: str = "en") -> str:
    lang_name = "French" if locale == "fr" else "English"
    metrics_json = json.dumps(analytics, ensure_ascii=False, indent=2)

    return (
        f"You are a senior financial analyst. Write an executive report in {lang_name} "
        "based STRICTLY on the verified JSON metrics below.\n\n"
        "STRICT GROUNDING RULES:\n"
        "1. NEVER invent, estimate, or extrapolate any number not present in the JSON.\n"
        "2. Every currency value, percentage, count, or quantity you write MUST match a number in the JSON. Do NOT compute new derived ratios (e.g. units per order) and keep dates in YYYY-MM-DD or YYYY-MM format.\n"
        "3. Separate objective numbers (facts) from business meaning (interpretations) and next steps (recommendations).\n"
        "4. Return ONLY valid JSON with these exact keys:\n"
        '   {"headline": "...", "executive_summary": "...", "facts": ["..."], "interpretations": ["..."], "recommendations": ["..."]}\n\n'
        f"VERIFIED METRICS JSON:\n{metrics_json}"
    )


def _parse_json_response(raw_text: str) -> dict[str, Any]:
    text = raw_text.strip()
    if text.startswith("```"):
        text = re.sub(r"^```(?:json)?\s*", "", text)
        text = re.sub(r"\s*```$", "", text)
    parsed = json.loads(text)
    if not isinstance(parsed, dict):
        raise ValueError("LLM response was not a JSON object.")
    return parsed


def _call_gemini(prompt: str, api_key: str, model: str) -> dict[str, Any]:
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent"
    payload = {
        "contents": [{"parts": [{"text": prompt}]}],
        "generationConfig": {
            "temperature": 0.2,
            "responseMimeType": "application/json",
        },
    }
    with httpx.Client(timeout=20.0) as client:
        resp = client.post(url, params={"key": api_key}, json=payload)
        resp.raise_for_status()
        data = resp.json()

    text = data["candidates"][0]["content"]["parts"][0]["text"]
    return _parse_json_response(text)


def _call_openai_compatible(prompt: str, api_key: str, model: str, base_url: str) -> dict[str, Any]:
    endpoint = (base_url.rstrip("/") if base_url else "https://api.openai.com/v1") + "/chat/completions"
    headers = {"Content-Type": "application/json"}
    if api_key:
        headers["Authorization"] = f"Bearer {api_key}"

    payload = {
        "model": model,
        "temperature": 0.2,
        "response_format": {"type": "json_object"},
        "messages": [
            {"role": "system", "content": "Return strictly grounded valid JSON only."},
            {"role": "user", "content": prompt},
        ],
    }
    with httpx.Client(timeout=20.0) as client:
        resp = client.post(endpoint, headers=headers, json=payload)
        resp.raise_for_status()
        data = resp.json()

    text = data["choices"][0]["message"]["content"]
    return _parse_json_response(text)


def generate_llm_summary(analytics: dict[str, Any], locale: str = "en") -> dict[str, Any] | None:
    cfg = get_llm_config()
    provider = cfg["provider"]

    if provider in ("", "none"):
        return None
    if provider == "gemini" and not cfg["api_key"]:
        return None

    prompt = build_grounding_prompt(analytics, locale=locale)

    try:
        if provider == "gemini":
            raw = _call_gemini(prompt, cfg["api_key"], cfg["model"])
        elif provider == "openai_compatible":
            raw = _call_openai_compatible(prompt, cfg["api_key"], cfg["model"], cfg["base_url"])
        else:
            return None
    except Exception:
        return None

    required_str = ("headline", "executive_summary")
    required_list = ("facts", "interpretations", "recommendations")
    if not all(isinstance(raw.get(k), str) and raw[k].strip() for k in required_str):
        return None
    if not all(isinstance(raw.get(k), list) for k in required_list):
        return None

    candidate = {
        "generation_mode": f"llm:{cfg['model']}",
        "locale": locale,
        "headline": raw["headline"].strip(),
        "executive_summary": raw["executive_summary"].strip(),
        "facts": [str(x).strip() for x in raw["facts"] if str(x).strip()],
        "interpretations": [str(x).strip() for x in raw["interpretations"] if str(x).strip()],
        "recommendations": [str(x).strip() for x in raw["recommendations"] if str(x).strip()],
    }

    check = verify_numbers_against_metrics(candidate, analytics)
    if not check["passed"]:
        # Reject hallucinated numbers so caller falls back to verified rule-based output
        return None

    candidate["key_findings"] = candidate["facts"]
    candidate["number_check"] = check
    return candidate