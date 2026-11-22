from typing import Any

from app.services.kpi_engine import analyze_dataset


def build_executive_summary(analytics: dict[str, Any], locale: str = "en") -> dict[str, Any]:
    lang = "fr" if str(locale).strip().lower() == "fr" else "en"
    kpis = analytics["kpis"]
    categories = analytics.get("category_breakdown", [])
    top_products = analytics.get("top_products", [])
    comparison = analytics.get("period_comparison")

    total_revenue = float(kpis["total_revenue"])
    total_orders = int(kpis["total_orders"])
    aov = float(kpis["average_order_value"])
    date_min = str(kpis["date_min"])
    date_max = str(kpis["date_max"])

    if lang == "fr":
        if comparison and comparison.get("revenue_change_pct") is not None:
            pct = float(comparison["revenue_change_pct"])
            direction = "a progressé de" if pct >= 0 else "a reculé de"
            headline = (
                f"Le chiffre d'affaires {direction} {abs(pct):.2f}% en {comparison['current_month']} "
                f"à ${comparison['current_revenue']:,.2f} sur un total de ${total_revenue:,.2f}."
            )
        else:
            headline = (
                f"Chiffre d'affaires de ${total_revenue:,.2f} sur {total_orders} commandes "
                f"du {date_min} au {date_max}."
            )

        summary_parts = [
            f"Entre le {date_min} et le {date_max}, l'activité a enregistré ${total_revenue:,.2f} "
            f"de chiffre d'affaires sur {total_orders} commandes uniques (panier moyen : ${aov:,.2f})."
        ]
        if categories:
            lead_cat = categories[0]
            summary_parts.append(
                f"La catégorie {lead_cat['category']} arrive en tête avec ${lead_cat['revenue']:,.2f} "
                f"({lead_cat['revenue_share_pct']:.2f}% du chiffre d'affaires total)."
            )
        if comparison and comparison.get("revenue_change_pct") is not None:
            pct = float(comparison["revenue_change_pct"])
            trend_word = "hausse" if pct >= 0 else "baisse"
            summary_parts.append(
                f"En variation mensuelle, le chiffre d'affaires affiche une {trend_word} de {abs(pct):.2f}% "
                f"entre {comparison['previous_month']} (${comparison['previous_revenue']:,.2f}) "
                f"et {comparison['current_month']} (${comparison['current_revenue']:,.2f})."
            )

        key_findings = [
            f"Chiffre d'affaires total : ${total_revenue:,.2f} sur {total_orders} commandes (panier moyen : ${aov:,.2f})."
        ]
        if categories:
            lead_cat = categories[0]
            key_findings.append(
                f"Catégorie principale : {lead_cat['category']} a généré ${lead_cat['revenue']:,.2f} "
                f"({lead_cat['revenue_share_pct']:.2f}% de part)."
            )
        if top_products:
            lead_prod = top_products[0]
            key_findings.append(
                f"Produit phare : {lead_prod['product']} ({lead_prod['category']}) a rapporté "
                f"${lead_prod['revenue']:,.2f} pour {lead_prod['units']:g} unités."
            )
        if comparison:
            rev_change = float(comparison["revenue_change"])
            sign = "+" if rev_change >= 0 else "-"
            pct_text = (
                f" ({comparison['revenue_change_pct']:+.2f}%)"
                if comparison.get("revenue_change_pct") is not None
                else ""
            )
            key_findings.append(
                f"Le dernier mois ({comparison['current_month']}) a varié de "
                f"{sign}${abs(rev_change):,.2f}{pct_text} par rapport à {comparison['previous_month']}."
            )

        recommendations: list[str] = []
        if categories and float(categories[0]["revenue_share_pct"]) >= 50.0:
            recommendations.append(
                f"Diversifier les revenus au-delà de {categories[0]['category']} "
                f"({categories[0]['revenue_share_pct']:.2f}% de part) afin de réduire le risque de concentration."
            )
        if comparison and comparison.get("revenue_change_pct") is not None:
            pct = float(comparison["revenue_change_pct"])
            if pct < 0:
                recommendations.append(
                    f"Analyser la baisse de {abs(pct):.2f}% en {comparison['current_month']} "
                    "et revoir la tarification, les promotions ou la disponibilité des stocks."
                )
            elif pct > 0:
                recommendations.append(
                    f"Capitaliser sur la croissance de {pct:.2f}% en {comparison['current_month']} "
                    "en sécurisant les stocks des produits les plus vendus."
                )
        if top_products:
            recommendations.append(
                f"Proposer des offres groupées autour de {top_products[0]['product']} pour augmenter le panier moyen au-dessus de ${aov:,.2f}."
            )

        return {
            "generation_mode": "rule_based",
            "locale": "fr",
            "headline": headline,
            "executive_summary": " ".join(summary_parts),
            "key_findings": key_findings,
            "recommendations": recommendations,
        }

    # English (default)
    if comparison and comparison.get("revenue_change_pct") is not None:
        pct = float(comparison["revenue_change_pct"])
        direction = "grew" if pct >= 0 else "declined"
        headline = (
            f"Revenue {direction} {abs(pct):.2f}% in {comparison['current_month']} "
            f"to ${comparison['current_revenue']:,.2f} across ${total_revenue:,.2f} total sales."
        )
    else:
        headline = (
            f"Generated ${total_revenue:,.2f} across {total_orders} orders "
            f"from {date_min} to {date_max}."
        )

    summary_parts = [
        f"Between {date_min} and {date_max}, the business recorded ${total_revenue:,.2f} "
        f"in total revenue across {total_orders} unique orders (Average Order Value: ${aov:,.2f})."
    ]

    if categories:
        lead_cat = categories[0]
        summary_parts.append(
            f"{lead_cat['category']} led performance with ${lead_cat['revenue']:,.2f} "
            f"({lead_cat['revenue_share_pct']:.2f}% of total revenue)."
        )

    if comparison and comparison.get("revenue_change_pct") is not None:
        pct = float(comparison["revenue_change_pct"])
        trend_word = "increase" if pct >= 0 else "decrease"
        summary_parts.append(
            f"Month-over-month revenue showed a {abs(pct):.2f}% {trend_word} "
            f"from {comparison['previous_month']} (${comparison['previous_revenue']:,.2f}) "
            f"to {comparison['current_month']} (${comparison['current_revenue']:,.2f})."
        )

    key_findings = [
        f"Total revenue reached ${total_revenue:,.2f} across {total_orders} orders (AOV: ${aov:,.2f})."
    ]

    if categories:
        lead_cat = categories[0]
        key_findings.append(
            f"Top category: {lead_cat['category']} generated ${lead_cat['revenue']:,.2f} "
            f"({lead_cat['revenue_share_pct']:.2f}% share)."
        )

    if top_products:
        lead_prod = top_products[0]
        key_findings.append(
            f"Top product: {lead_prod['product']} ({lead_prod['category']}) brought in "
            f"${lead_prod['revenue']:,.2f} across {lead_prod['units']:g} units."
        )

    if comparison:
        rev_change = float(comparison["revenue_change"])
        sign = "+" if rev_change >= 0 else "-"
        pct_text = (
            f" ({comparison['revenue_change_pct']:+.2f}%)"
            if comparison.get("revenue_change_pct") is not None
            else ""
        )
        key_findings.append(
            f"Latest month ({comparison['current_month']}) changed by "
            f"{sign}${abs(rev_change):,.2f}{pct_text} versus {comparison['previous_month']}."
        )

    recommendations = []

    if categories and float(categories[0]["revenue_share_pct"]) >= 50.0:
        recommendations.append(
            f"Diversify revenue beyond {categories[0]['category']} "
            f"({categories[0]['revenue_share_pct']:.2f}% share) to reduce category concentration risk."
        )

    if comparison and comparison.get("revenue_change_pct") is not None:
        pct = float(comparison["revenue_change_pct"])
        if pct < 0:
            recommendations.append(
                f"Investigate the {abs(pct):.2f}% revenue drop in {comparison['current_month']} "
                "and review pricing, promotions, or stock availability."
            )
        elif pct > 0:
            recommendations.append(
                f"Capitalize on the {pct:.2f}% growth momentum in {comparison['current_month']} "
                "by ensuring inventory depth for top-selling products."
            )

    if top_products:
        recommendations.append(
            f"Bundle {top_products[0]['product']} with lower-performing items to lift Average Order Value above ${aov:,.2f}."
        )

    return {
        "generation_mode": "rule_based",
        "locale": "en",
        "headline": headline,
        "executive_summary": " ".join(summary_parts),
        "key_findings": key_findings,
        "recommendations": recommendations,
    }


def summarize_dataset(
    dataset_id: str,
    start_date: str | None = None,
    end_date: str | None = None,
    locale: str = "en",
) -> dict[str, Any]:
    analytics = analyze_dataset(
        dataset_id=dataset_id,
        start_date=start_date,
        end_date=end_date,
    )
    summary = build_executive_summary(analytics, locale=locale)

    return {
        "dataset_id": dataset_id,
        "summary": summary,
        "analytics": analytics,
    }