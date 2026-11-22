from fastapi import APIRouter
from fastapi.responses import JSONResponse
from pydantic import BaseModel

from app.services.csv_validator import ValidationInputError
from app.services.summary_engine import summarize_dataset

router = APIRouter()


class SummarizeRequest(BaseModel):
    dataset_id: str
    start_date: str | None = None
    end_date: str | None = None


@router.post("/summarize")
def summarize_dataset_endpoint(payload: SummarizeRequest):
    try:
        result = summarize_dataset(
            dataset_id=payload.dataset_id,
            start_date=payload.start_date,
            end_date=payload.end_date,
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