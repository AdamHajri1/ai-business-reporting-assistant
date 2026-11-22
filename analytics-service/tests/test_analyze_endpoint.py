from fastapi.testclient import TestClient

from app.main import app
from app.services import csv_validator

client = TestClient(app)

SAMPLE_CSV = """date,order_id,product,category,quantity,unit_price
2026-01-10,ORD-001,Laptop Stand,Accessories,2,35.00
2026-01-10,ORD-001,USB-C Hub,Accessories,1,50.00
2026-02-05,ORD-002,Monitor,Displays,1,160.00
"""


def test_analyze_valid_dataset(tmp_path, monkeypatch):
    dataset_id = "a" * 32
    (tmp_path / f"{dataset_id}.csv").write_text(SAMPLE_CSV, encoding="utf-8")
    monkeypatch.setattr(csv_validator, "UPLOADS_DIR", tmp_path)

    response = client.post("/analyze", json={"dataset_id": dataset_id})

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    assert body["error"] is None
    assert body["data"]["dataset_id"] == dataset_id
    assert body["data"]["kpis"]["total_revenue"] == 280.0
    assert body["data"]["kpis"]["total_orders"] == 2
    assert body["data"]["period_comparison"]["current_month"] == "2026-02"


def test_analyze_with_date_filter(tmp_path, monkeypatch):
    dataset_id = "b" * 32
    (tmp_path / f"{dataset_id}.csv").write_text(SAMPLE_CSV, encoding="utf-8")
    monkeypatch.setattr(csv_validator, "UPLOADS_DIR", tmp_path)

    response = client.post(
        "/analyze",
        json={
            "dataset_id": dataset_id,
            "start_date": "2026-02-01",
            "end_date": "2026-02-28",
        },
    )

    assert response.status_code == 200
    body = response.json()
    assert body["data"]["kpis"]["total_revenue"] == 160.0
    assert body["data"]["kpis"]["total_orders"] == 1
    assert body["data"]["period_comparison"] is None


def test_analyze_invalid_dataset_id():
    response = client.post("/analyze", json={"dataset_id": "../secret"})

    assert response.status_code == 400
    body = response.json()
    assert body["status"] == "error"
    assert body["error"]["code"] == "INVALID_DATASET_ID"


def test_analyze_missing_dataset(tmp_path, monkeypatch):
    monkeypatch.setattr(csv_validator, "UPLOADS_DIR", tmp_path)
    response = client.post("/analyze", json={"dataset_id": "c" * 32})

    assert response.status_code == 404
    body = response.json()
    assert body["status"] == "error"
    assert body["error"]["code"] == "DATASET_NOT_FOUND"