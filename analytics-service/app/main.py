from fastapi import FastAPI

from app.routers import health

app = FastAPI(title="AI Business Reporting Analytics Service")

app.include_router(health.router)