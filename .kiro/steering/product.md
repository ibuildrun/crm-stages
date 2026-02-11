# Product Overview

CRM Stages is a sales pipeline management component designed for Joomla 4 (`com_crmstages`). It enforces a strict sequential stage funnel where managers cannot skip ahead without completing required actions at each stage.

## Core Concept

Companies progress through a 10-stage sales funnel (Ice → Touched → Aware → Interested → Demo Planned → Demo Done → Committed → Customer → Activated). Each stage has exit conditions (required events) and action restrictions. A "Null" rejection stage is reachable from any active stage and is terminal alongside "Activated".

## Key Business Rules

- Stage transitions are strictly sequential — no skipping stages
- Each stage defines exit conditions that must be satisfied before advancing
- Actions (call, demo, invoice, etc.) are restricted based on current stage
- Demo freshness rule: Demo Done requires the demo to have been conducted within 60 days
- Null (rejection) is reachable from any non-terminal stage
- Optimistic locking prevents concurrent stage conflicts
- Events are append-only (event sourcing lite pattern)

## Domain Language

- ЛПР = decision-maker (лицо, принимающее решения)
- Дискавери = discovery form
- КП = commercial proposal (коммерческое предложение)
- MLS codes: C0–C2 (cold), W1–W3 (warm), H1–H2 (hot), A1 (activated), N0 (null)
