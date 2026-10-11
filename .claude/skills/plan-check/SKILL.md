---
name: plan-check
description: Verify that a plan in .ai/plans/ is fully and correctly implemented. Runs its Verify section and checks every task and decision against the code. Use when the user says "plan-check", "verify the plan" or a planned change is finished.
argument-hint: "[plan file]"
---

Check the plan: $ARGUMENTS

1. **Pick the plan.** Use the given file, otherwise the plan in `.ai/plans/` with `Status: in progress`. If that is ambiguous, ask.
2. **Run the Verify section.** Run every command it lists and report the results faithfully, including failures.
3. **Check against the code.** For each ticked task, confirm that the code actually does it. For each decision, confirm that the implementation follows it. Also check the project constraints in CLAUDE.md that apply (schema changes through the `build*` methods, `_q()` keys, no `RefreshDatabase`, Pint).
4. **Report** in three groups: ✅ done and verified, ⚠️ done but deviating or untested, ❌ missing or failing, each with a `path:line` reference. Do not fix anything in this step; offer to fix the items in ⚠️ and ❌.
5. **Close.** When everything passes, set `Status: done` and suggest running `/code-review` before committing.
