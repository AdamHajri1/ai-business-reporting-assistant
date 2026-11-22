from typing import Any

import pandas as pd

from app.services.csv_validator import (
    REQUIRED_COLUMNS,
    ValidationInputError,
    resolve_dataset_path,
)


def _prepare_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    cleaned = df.copy()
    cleaned.columns = [str(col).strip().lower() for col in cleaned.columns]

    missing = [col for col in REQUIRED_COLUMNS if col not in cleaned.columns]
    if missing:
        raise ValidationInputError(
            code="MISSING_COLUMNS",
            message=f"Missing required columns: {', '.join(missing)}",
            status_code=422,
        )

    cleaned["date"] = pd.to_datetime(cleaned["date"].astype(str).str.strip(), errors="coerce")
    cleaned["order_id"] = cleaned["order_id"].astype(str).str.strip()
    cleaned["product"] = cleaned["product"].astype(str).str.strip()
    cleaned["category"] = cleaned["category"].astype(str).str.strip()
    cleaned["quantity"] = pd.to_numeric(cleaned["quantity"].astype(str).str.strip(), errors="coerce")
    cleaned["unit_price"] = pd.to_numeric(cleaned["unit_price"].astype(str).str.strip(), errors="coerce")

    valid_mask = (
        cleaned["date"].notna()
        & (cleaned["order_id"] != "")
        & (cleaned["product"] != "")
        & (cleaned["category"] != "")
        & cleaned["quantity"].notna()
        & (cleaned["quantity"] > 0)
        & cleaned["unit_price"].notna()
        & (cleaned["unit_price"] >= 0)
    )

    cleaned = cleaned.loc[valid_mask].copy()
    if cleaned.empty:
        raise ValidationInputError(
            code="NO_VALID_ROWS",
            message="No valid rows available for analytics.",
            status_code=422,
        )

    cleaned["revenue"] = cleaned["quantity"] * cleaned["unit_price"]
    cleaned["month"] = cleaned["date"].dt.strftime("%Y-%m")
    return cleaned


def compute_analytics(
    df: pd.DataFrame,
    start_date: str | None = None,
    end_date: str | None = None,
    top_n: int = 10,
) -> dict[str, Any]:
    data = _prepare_dataframe(df)

    if start_date:
        start_ts = pd.to_datetime(start_date, errors="coerce")
        if pd.isna(start_ts):
            raise ValidationInputError("INVALID_DATE_FILTER", "Invalid start_date format (expected YYYY-MM-DD).", 400)
        data = data.loc[data["date"] >= start_ts]

    if end_date:
        end_ts = pd.to_datetime(end_date, errors="coerce")
        if pd.isna(end_ts):
            raise ValidationInputError("INVALID_DATE_FILTER", "Invalid end_date format (expected YYYY-MM-DD).", 400)
        data = data.loc[data["date"] <= end_ts]

    if data.empty:
        raise ValidationInputError(
            code="EMPTY_FILTERED_DATASET",
            message="No rows match the selected date range.",
            status_code=422,
        )

    total_revenue = round(float(data["revenue"].sum()), 2)
    total_orders = int(data["order_id"].nunique())
    total_units = round(float(data["quantity"].sum()), 2)
    aov = round(total_revenue / total_orders, 2) if total_orders > 0 else 0.0

    kpis = {
        "total_revenue": total_revenue,
        "total_orders": total_orders,
        "total_units_sold": total_units,
        "average_order_value": aov,
        "unique_products": int(data["product"].nunique()),
        "unique_categories": int(data["category"].nunique()),
        "row_count": int(len(data)),
        "date_min": data["date"].min().strftime("%Y-%m-%d"),
        "date_max": data["date"].max().strftime("%Y-%m-%d"),
    }

    # Monthly trends sorted chronologically
    monthly_grouped = (
        data.groupby("month", as_index=False)
        .agg(
            revenue=("revenue", "sum"),
            orders=("order_id", "nunique"),
            units=("quantity", "sum"),
        )
        .sort_values("month")
    )
    monthly_trends = [
        {
            "month": str(row["month"]),
            "revenue": round(float(row["revenue"]), 2),
            "orders": int(row["orders"]),
            "units": round(float(row["units"]), 2),
        }
        for _, row in monthly_grouped.iterrows()
    ]

    # Category breakdown sorted by revenue descending
    category_grouped = (
        data.groupby("category", as_index=False)
        .agg(
            revenue=("revenue", "sum"),
            orders=("order_id", "nunique"),
            units=("quantity", "sum"),
        )
        .sort_values("revenue", ascending=False)
    )
    category_breakdown = [
        {
            "category": str(row["category"]),
            "revenue": round(float(row["revenue"]), 2),
            "revenue_share_pct": round((float(row["revenue"]) / total_revenue) * 100, 2) if total_revenue > 0 else 0.0,
            "orders": int(row["orders"]),
            "units": round(float(row["units"]), 2),
        }
        for _, row in category_grouped.iterrows()
    ]

    # Top products sorted by revenue descending
    product_grouped = (
        data.groupby("product", as_index=False)
        .agg(
            category=("category", "first"),
            revenue=("revenue", "sum"),
            units=("quantity", "sum"),
            orders=("order_id", "nunique"),
        )
        .sort_values("revenue", ascending=False)
        .head(top_n)
    )
    top_products = [
        {
            "product": str(row["product"]),
            "category": str(row["category"]),
            "revenue": round(float(row["revenue"]), 2),
            "units": round(float(row["units"]), 2),
            "orders": int(row["orders"]),
        }
        for _, row in product_grouped.iterrows()
    ]

    # Month-over-month comparison (latest month vs previous month)
    period_comparison: dict[str, Any] | None = None
    if len(monthly_trends) >= 2:
        prev = monthly_trends[-2]
        curr = monthly_trends[-1]
        rev_change = round(curr["revenue"] - prev["revenue"], 2)
        rev_change_pct = (
            round((rev_change / prev["revenue"]) * 100, 2)
            if prev["revenue"] > 0
            else None
        )
        orders_change_pct = (
            round(((curr["orders"] - prev["orders"]) / prev["orders"]) * 100, 2)
            if prev["orders"] > 0
            else None
        )
        period_comparison = {
            "current_month": curr["month"],
            "previous_month": prev["month"],
            "current_revenue": curr["revenue"],
            "previous_revenue": prev["revenue"],
            "revenue_change": rev_change,
            "revenue_change_pct": rev_change_pct,
            "current_orders": curr["orders"],
            "previous_orders": prev["orders"],
            "orders_change_pct": orders_change_pct,
        }

    return {
        "kpis": kpis,
        "monthly_trends": monthly_trends,
        "category_breakdown": category_breakdown,
        "top_products": top_products,
        "period_comparison": period_comparison,
    }


def analyze_dataset(
    dataset_id: str,
    start_date: str | None = None,
    end_date: str | None = None,
) -> dict[str, Any]:
    file_path = resolve_dataset_path(dataset_id)

    try:
        df = pd.read_csv(file_path, dtype=str, keep_default_na=False)
    except Exception as exc:
        raise ValidationInputError(
            code="CSV_PARSE_ERROR",
            message=f"Could not parse CSV file: {exc}",
            status_code=422,
        ) from exc

    result = compute_analytics(df, start_date=start_date, end_date=end_date)
    return {
        "dataset_id": dataset_id,
        **result,
    }