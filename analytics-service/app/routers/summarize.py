from typing import Any

from fastapi import APIRouter
from fastapi.responses import JSONResponse
from pydantic import BaseModel

from app.services.csv_validator import ValidationInputError
from app.services.summary_engine import summarize_dataset, summarize_metrics

router = APIRouter()


class SummarizeRequest(BaseModel):
    dataset_id: str
    start_date: str | None = None
    end_date: str | None = None
    locale: str = "en"


class SummarizeMetricsRequest(BaseModel):
    analytics: dict[str, Any]
    locale: str = "en"


@router.post("/summarize")
def summarize_dataset_endpoint(payload: SummarizeRequest):
    try:
        result = summarize_dataset(
            dataset_id=payload.dataset_id,
            start_date=payload.start_date,
            end_date=payload.end_date,
            locale=payload.locale,
        )
    except ValidationInputError as exc:
        return JSONResponse(
            status_code=exc.status_code,
            content={
                "status": "error",
                "data": None,
                "error": {
                    "code": exc.code,
                    "message": exc.message,
                },
            },
        )

    return {
        "status": "ok",
        "data": result,
        "error": None,
    }


@router.post("/summarize/from-metrics")
def summarize_from_metrics_endpoint(payload: SummarizeMetricsRequest):
    if not isinstance(payload.analytics.get("kpis"), dict):
        return JSONResponse(
            status_code=400,
            content={
                "status": "error",
                "data": None,
                "error": {
                    "code": "INVALID_ANALYTICS",
                    "message": "Analytics payload must include a valid kpis object.",
                },
            },
        )

    summary = summarize_metrics(payload.analytics, locale=payload.locale)
    return {
        "status": "ok",
        "data": {"summary": summary},
        "error": None,
    }