from fastapi import FastAPI

from app.routers import health, validate

app = FastAPI(title="AI Business Reporting Analytics Service")

app.include_router(health.router)
app.include_router(validate.router)