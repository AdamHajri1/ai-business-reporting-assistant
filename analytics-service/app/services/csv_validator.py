from pathlib import Path
import re
from typing import Any

import pandas as pd

REQUIRED_COLUMNS = (
    "date",
    "order_id",
    "product",
    "category",
    "quantity",
    "unit_price",
)
MAX_ROWS = 50_000
MAX_PREVIEW_ROWS = 5
MAX_ERROR_SAMPLES = 10

DATASET_ID_PATTERN = re.compile(r"^[a-f0-9]{32}$")
UPLOADS_DIR = Path(__file__).resolve().parents[3] / "storage" / "uploads"


class ValidationInputError(Exception):
    def __init__(self, code: str, message: str, status_code: int = 400) -> None:
        super().__init__(message)
        self.code = code
        self.message = message
        self.status_code = status_code


def resolve_dataset_path(dataset_id: str) -> Path:
    if not DATASET_ID_PATTERN.fullmatch(dataset_id):
        raise ValidationInputError(
            code="INVALID_DATASET_ID",
            message="Dataset ID must be a 32-character lowercase hexadecimal string.",
            status_code=400,
        )

    file_path = (UPLOADS_DIR / f"{dataset_id}.csv").resolve()
    if file_path.parent != UPLOADS_DIR.resolve() or not file_path.is_file():
        raise ValidationInputError(
            code="DATASET_NOT_FOUND",
            message="Dataset file could not be found in private storage.",
            status_code=404,
        )

    return file_path


def validate_csv_dataset(dataset_id: str) -> dict[str, Any]:
    file_path = resolve_dataset_path(dataset_id)

    try:
        df = pd.read_csv(file_path, dtype=str, keep_default_na=False)
    except Exception as exc:
        raise ValidationInputError(
            code="CSV_PARSE_ERROR",
            message=f"Could not parse CSV file: {exc}",
            status_code=422,
        ) from exc

    df.columns = [str(col).strip().lower() for col in df.columns]
    missing_columns = [col for col in REQUIRED_COLUMNS if col not in df.columns]

    row_count = int(len(df))
    if missing_columns:
        return {
            "dataset_id": dataset_id,
            "is_valid": False,
            "row_count": row_count,
            "valid_row_count": 0,
            "error_row_count": row_count,
            "date_min": None,
            "date_max": None,
            "missing_columns": missing_columns,
            "preview": [],
            "row_errors": [
                {
                    "row": 1,
                    "reason": f"Missing required columns: {', '.join(missing_columns)}",
                }
            ],
        }

    if row_count == 0:
        return {
            "dataset_id": dataset_id,
            "is_valid": False,
            "row_count": 0,
            "valid_row_count": 0,
            "error_row_count": 0,
            "date_min": None,
            "date_max": None,
            "missing_columns": [],
            "preview": [],
            "row_errors": [{"row": 1, "reason": "CSV file contains headers but no data rows."}],
        }

    if row_count > MAX_ROWS:
        raise ValidationInputError(
            code="ROW_LIMIT_EXCEEDED",
            message=f"CSV contains {row_count} rows, exceeding the maximum of {MAX_ROWS} rows.",
            status_code=422,
        )

    parsed_dates = pd.to_datetime(df["date"].str.strip(), errors="coerce")
    parsed_qty = pd.to_numeric(df["quantity"].str.strip(), errors="coerce")
    parsed_price = pd.to_numeric(df["unit_price"].str.strip(), errors="coerce")

    valid_date = parsed_dates.notna()
    valid_order = df["order_id"].str.strip() != ""
    valid_product = df["product"].str.strip() != ""
    valid_category = df["category"].str.strip() != ""
    valid_qty = parsed_qty.notna() & (parsed_qty > 0)
    valid_price = parsed_price.notna() & (parsed_price >= 0)

    valid_mask = (
        valid_date
        & valid_order
        & valid_product
        & valid_category
        & valid_qty
        & valid_price
    )

    valid_row_count = int(valid_mask.sum())
    error_row_count = row_count - valid_row_count

    row_errors: list[dict[str, Any]] = []
    if error_row_count > 0:
        invalid_indices = df.index[~valid_mask][:MAX_ERROR_SAMPLES]
        for idx in invalid_indices:
            reasons: list[str] = []
            if not valid_date.loc[idx]:
                reasons.append("invalid date")
            if not valid_order.loc[idx]:
                reasons.append("empty order_id")
            if not valid_product.loc[idx]:
                reasons.append("empty product")
            if not valid_category.loc[idx]:
                reasons.append("empty category")
            if not valid_qty.loc[idx]:
                reasons.append("quantity must be a number greater than 0")
            if not valid_price.loc[idx]:
                reasons.append("unit_price must be a number 0 or greater")

            row_errors.append(
                {
                    "row": int(idx) + 2,  # +2 accounts for 0-based index and header row
                    "reason": "; ".join(reasons),
                }
            )

    date_min = None
    date_max = None
    preview: list[dict[str, Any]] = []

    if valid_row_count > 0:
        valid_dates_only = parsed_dates[valid_mask]
        date_min = valid_dates_only.min().strftime("%Y-%m-%d")
        date_max = valid_dates_only.max().strftime("%Y-%m-%d")

        for idx in df.index[valid_mask][:MAX_PREVIEW_ROWS]:
            preview.append(
                {
                    "date": parsed_dates.loc[idx].strftime("%Y-%m-%d"),
                    "order_id": df.at[idx, "order_id"].strip(),
                    "product": df.at[idx, "product"].strip(),
                    "category": df.at[idx, "category"].strip(),
                    "quantity": float(parsed_qty.loc[idx]),
                    "unit_price": float(parsed_price.loc[idx]),
                }
            )

    return {
        "dataset_id": dataset_id,
        "is_valid": valid_row_count > 0 and error_row_count == 0,
        "row_count": row_count,
        "valid_row_count": valid_row_count,
        "error_row_count": error_row_count,
        "date_min": date_min,
        "date_max": date_max,
        "missing_columns": [],
        "preview": preview,
        "row_errors": row_errors,
    }