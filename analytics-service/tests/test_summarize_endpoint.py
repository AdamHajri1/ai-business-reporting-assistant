from fastapi.testclient import TestClient

from app.main import app
from app.services import csv_validator

client = TestClient(app)

SAMPLE_CSV = """date,order_id,product,category,quantity,unit_price
2026-01-10,ORD-001,Laptop Stand,Accessories,2,35.00
2026-01-10,ORD-001,USB-C Hub,Accessories,1,50.00
2026-01-20,ORD-002,Mechanical Keyboard,Peripherals,1,80.00
2026-02-05,ORD-003,Laptop Stand,Accessories,4,35.00
2026-02-15,ORD-004,Monitor,Displays,1,160.00
"""


def test_summarize_valid_dataset(tmp_path, monkeypatch):
    dataset_id = "d" * 32
    (tmp_path / f"{dataset_id}.csv").write_text(SAMPLE_CSV, encoding="utf-8")
    monkeypatch.setattr(csv_validator, "UPLOADS_DIR", tmp_path)

    response = client.post("/summarize", json={"dataset_id": dataset_id})

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "ok"
    summary = body["data"]["summary"]
    assert summary["generation_mode"] == "rule_based"
    assert "50.00%" in summary["headline"]
    assert "$500.00" in summary["executive_summary"]
    assert len(summary["key_findings"]) >= 4
    assert len(summary["recommendations"]) >= 2


def test_summarize_single_month_filter(tmp_path, monkeypatch):
    dataset_id = "e" * 32
    (tmp_path / f"{dataset_id}.csv").write_text(SAMPLE_CSV, encoding="utf-8")
    monkeypatch.setattr(csv_validator, "UPLOADS_DIR", tmp_path)

    response = client.post(
        "/summarize",
        json={"dataset_id": dataset_id, "start_date": "2026-02-01"},
    )

    assert response.status_code == 200
    body = response.json()
    assert "$300.00" in body["data"]["summary"]["headline"]
    assert body["data"]["analytics"]["period_comparison"] is None


def test_summarize_invalid_dataset_id():
    response = client.post("/summarize", json={"dataset_id": "../bad"})

    assert response.status_code == 400
    assert response.json()["error"]["code"] == "INVALID_DATASET_ID"