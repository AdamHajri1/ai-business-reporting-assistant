from fastapi import APIRouter
from fastapi.responses import JSONResponse
from pydantic import BaseModel

from app.services.csv_validator import ValidationInputError, validate_csv_dataset

router = APIRouter()


class ValidateRequest(BaseModel):
    dataset_id: str


@router.post("/validate")
def validate_dataset(payload: ValidateRequest):
    try:
        result = validate_csv_dataset(payload.dataset_id)
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