from fastapi import FastAPI

from app.routers import analyze, health, summarize, validate

app = FastAPI(title="AI Business Reporting Analytics Service")

app.include_router(health.router)
app.include_router(validate.router)
app.include_router(analyze.router)
app.include_router(summarize.router)