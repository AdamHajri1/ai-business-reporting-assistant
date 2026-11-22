import pandas as pd

from app.services import kpi_engine, llm_service


def _sample_analytics():
    df = pd.DataFrame(
        [
            {"date": "2026-01-10", "order_id": "ORD-001", "product": "Laptop Stand", "category": "Accessories", "quantity": "2", "unit_price": "35.00"},
            {"date": "2026-01-10", "order_id": "ORD-001", "product": "USB-C Hub", "category": "Accessories", "quantity": "1", "unit_price": "50.00"},
            {"date": "2026-01-20", "order_id": "ORD-002", "product": "Mechanical Keyboard", "category": "Peripherals", "quantity": "1", "unit_price": "80.00"},
            {"date": "2026-02-05", "order_id": "ORD-003", "product": "Laptop Stand", "category": "Accessories", "quantity": "4", "unit_price": "35.00"},
            {"date": "2026-02-15", "order_id": "ORD-004", "product": "Monitor", "category": "Displays", "quantity": "1", "unit_price": "160.00"},
        ]
    )
    return kpi_engine.compute_analytics(df)


def test_number_check_passes_on_grounded_output():
    analytics = _sample_analytics()
    grounded = {
        "headline": "Revenue grew 50.00% in 2026-02 to $300.00.",
        "executive_summary": "Total revenue reached $500.00 across 4 orders from 2026-01-10 to 2026-02-15.",
        "facts": ["Accessories generated $260.00 (52.00% share)."],
        "interpretations": ["Growth accelerated in 2026-02."],
        "recommendations": ["Lift AOV above $125.00."],
    }

    check = llm_service.verify_numbers_against_metrics(grounded, analytics)
    assert check["passed"] is True
    assert check["unverified_numbers"] == []
    assert check["checked_count"] >= 6


def test_number_check_catches_hallucinated_numbers():
    analytics = _sample_analytics()
    hallucinated = {
        "headline": "Revenue reached $999.99 with 87.5% growth.",
        "executive_summary": "Total orders hit 4.",
        "facts": [],
        "interpretations": [],
        "recommendations": [],
    }

    check = llm_service.verify_numbers_against_metrics(hallucinated, analytics)
    assert check["passed"] is False
    assert "$999.99" in check["unverified_numbers"]
    assert "87.5%" in check["unverified_numbers"]


def test_llm_rejects_hallucination_and_returns_none(monkeypatch):
    analytics = _sample_analytics()
    monkeypatch.setattr(
        llm_service,
        "get_llm_config",
        lambda: {"provider": "gemini", "api_key": "fake-key", "model": "gemini-2.5-flash", "base_url": ""},
    )
    monkeypatch.setattr(
        llm_service,
        "_call_gemini",
        lambda prompt, api_key, model: {
            "headline": "Revenue soared to $12,345.00!",
            "executive_summary": "Invented metric.",
            "facts": ["$12,345.00 revenue"],
            "interpretations": ["Strong quarter."],
            "recommendations": ["Expand."],
        },
    )

    assert llm_service.generate_llm_summary(analytics, locale="en") is None


def test_llm_accepts_verified_response(monkeypatch):
    analytics = _sample_analytics()
    monkeypatch.setattr(
        llm_service,
        "get_llm_config",
        lambda: {"provider": "gemini", "api_key": "fake-key", "model": "gemini-2.5-flash", "base_url": ""},
    )
    monkeypatch.setattr(
        llm_service,
        "_call_gemini",
        lambda prompt, api_key, model: {
            "headline": "Revenue grew 50.00% to $300.00 in 2026-02.",
            "executive_summary": "Total revenue reached $500.00 across 4 orders.",
            "facts": ["Average Order Value was $125.00."],
            "interpretations": ["Accessories drove 52.00% of sales."],
            "recommendations": ["Bundle Laptop Stand to grow order value."],
        },
    )

    result = llm_service.generate_llm_summary(analytics, locale="en")
    assert result is not None
    assert result["generation_mode"] == "llm:gemini-2.5-flash"
    assert result["number_check"]["passed"] is True