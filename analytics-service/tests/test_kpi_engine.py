import pandas as pd
import pytest

from app.services.csv_validator import ValidationInputError
from app.services.kpi_engine import compute_analytics


def _sample_df() -> pd.DataFrame:
    # ORD-001 intentionally has 2 line items to verify unique order counting
    return pd.DataFrame(
        [
            {"date": "2026-01-10", "order_id": "ORD-001", "product": "Laptop Stand", "category": "Accessories", "quantity": "2", "unit_price": "35.00"},
            {"date": "2026-01-10", "order_id": "ORD-001", "product": "USB-C Hub", "category": "Accessories", "quantity": "1", "unit_price": "50.00"},
            {"date": "2026-01-20", "order_id": "ORD-002", "product": "Mechanical Keyboard", "category": "Peripherals", "quantity": "1", "unit_price": "80.00"},
            {"date": "2026-02-05", "order_id": "ORD-003", "product": "Laptop Stand", "category": "Accessories", "quantity": "4", "unit_price": "35.00"},
            {"date": "2026-02-15", "order_id": "ORD-004", "product": "Monitor", "category": "Displays", "quantity": "1", "unit_price": "160.00"},
        ]
    )


def test_core_kpis_and_unique_orders():
    result = compute_analytics(_sample_df())
    kpis = result["kpis"]

    assert kpis["total_revenue"] == 500.0
    assert kpis["total_orders"] == 4
    assert kpis["total_units_sold"] == 9.0
    assert kpis["average_order_value"] == 125.0
    assert kpis["unique_products"] == 4
    assert kpis["unique_categories"] == 3
    assert kpis["date_min"] == "2026-01-10"
    assert kpis["date_max"] == "2026-02-15"


def test_monthly_trends_and_period_comparison():
    result = compute_analytics(_sample_df())

    assert result["monthly_trends"] == [
        {"month": "2026-01", "revenue": 200.0, "orders": 2, "units": 4.0},
        {"month": "2026-02", "revenue": 300.0, "orders": 2, "units": 5.0},
    ]

    comp = result["period_comparison"]
    assert comp is not None
    assert comp["current_month"] == "2026-02"
    assert comp["previous_month"] == "2026-01"
    assert comp["revenue_change"] == 100.0
    assert comp["revenue_change_pct"] == 50.0
    assert comp["orders_change_pct"] == 0.0


def test_category_breakdown_and_top_products():
    result = compute_analytics(_sample_df())

    categories = result["category_breakdown"]
    assert categories[0] == {
        "category": "Accessories",
        "revenue": 260.0,
        "revenue_share_pct": 52.0,
        "orders": 2,
        "units": 7.0,
    }
    assert categories[1]["category"] == "Displays"
    assert categories[1]["revenue_share_pct"] == 32.0

    top = result["top_products"]
    assert top[0]["product"] == "Laptop Stand"
    assert top[0]["revenue"] == 210.0
    assert top[0]["units"] == 6.0


def test_date_filtering_and_single_month_comparison():
    result = compute_analytics(_sample_df(), start_date="2026-02-01")

    assert result["kpis"]["total_revenue"] == 300.0
    assert result["kpis"]["total_orders"] == 2
    assert result["period_comparison"] is None


def test_empty_filtered_range_raises():
    with pytest.raises(ValidationInputError) as exc_info:
        compute_analytics(_sample_df(), start_date="2027-01-01")

    assert exc_info.value.code == "EMPTY_FILTERED_DATASET"