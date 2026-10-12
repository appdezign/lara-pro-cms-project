---
name: plan-new
description: Start a lightweight plan for a change. Explores the code, asks clarifying questions and writes a plan file in .ai/plans/ from the template. Never changes code. Use when the user says "plan-new", "new plan" or wants to plan a change before building it.
argument-hint: <topic>
---

Create a plan for: $ARGUMENTS

**This step plans only. Do not change any code, config or database.** The only file you write is the plan file.

1. **Explore.** Read the code involved, the matching `.ai/rules` files (see `.ai/rules/index.md`) and the relevant CLAUDE.md sections. Check `.ai/plans/` for an existing plan on the same topic; if there is one, update it instead of creating a new file.
2. **Ask.** If the goal, the scope or a design choice is unclear, ask the user (at most a few questions, with a recommended option). Do not guess on things that are the user's to decide.
3. **Write** `.ai/plans/{YYYY-MM-DD}-{kebab-topic}.md`, using today's date and following `.ai/plans/_template.md`, with `Status: draft`:
   - **Goal**: what and why, briefly.
   - **Context**: concrete file paths (`path:line`), classes, tables and the project constraints that apply.
   - **Decisions**: each choice with its reason, including alternatives you rejected.
   - **Tasks**: small, ordered, checkable steps; each can be done and verified on its own. Include tests and Pint.
   - **Verify**: the exact test commands and any manual checks.
4. **Report** the file path and a short summary, and ask the user to review the plan. Building starts with `/plan-go`.
