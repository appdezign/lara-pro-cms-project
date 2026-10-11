---
name: plan-go
description: Implement the open tasks of a plan file in .ai/plans/, ticking them off and recording notes. Use when the user says "plan-go", "continue the plan" or wants to build or resume a planned change.
argument-hint: "[plan file]"
---

Implement the plan: $ARGUMENTS

1. **Pick the plan.** Use the given file. Without one, use the plan in `.ai/plans/` with `Status: in progress`, otherwise the most recent `draft`. If that is ambiguous, list the candidates and ask.
2. **Load context.** Read the whole plan, including Decisions and Notes, and the `.ai/rules` files that cover the paths in its tasks. Set `Status: in progress`.
3. **Work through the open tasks in order.** For each task:
   - Implement it, following CLAUDE.md and the rules.
   - Run the narrowest test that covers it, and Pint on the changed PHP files.
   - Tick it off (`- [x]`) in the plan file right away, so the plan stays accurate if the session ends.
4. **When reality differs from the plan** (a wrong assumption, a missing step, a better approach), stop and tell the user before deviating. Record what changed in Decisions or Notes, and add or adjust tasks.
5. **Finish** with a short report: tasks done, tasks open and anything noted. When all tasks are done, suggest `/plan-check`.
