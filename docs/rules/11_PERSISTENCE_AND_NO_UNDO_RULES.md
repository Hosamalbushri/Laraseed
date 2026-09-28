# 11. Implementation Persistence and No-Undo Operational Rules

## Status
Mandatory Repository-Wide Operational and Architectural Rule.

---

## 1. Fundamental Principle: Persistence of Tested Work
When a task or prompt requires physical implementation:
1. **The Tested Implementation Must Remain on Disk**: The exact code that passes automated verification and test suites must physically exist in the working tree when execution ends.
2. **No Cleanup/Revert Phase**: There is no permissible "cleanup" phase that reverts or resets the codebase to its baseline or pre-task state.
3. **Dirty Worktree is Expected for Uncommitted Work**: A clean Git status (`git status --short` empty) is **NOT** a success criterion for an uncommitted implementation task. Uncommitted changes and untracked files representing the implementation are expected and mandatory.

---

## 2. Strictly Prohibited Commands & Behaviors
The following commands and actions are prohibited before, during, and after implementation, as well as before, during, and after report generation:

```bash
# Prohibited Git commands:
git reset
git reset --hard
git restore
git checkout .
git checkout -- <file>
git clean
git stash
git stash push
git revert
```

Also prohibited:
- IDE Undo or editor reverts that remove verified code.
- Deleting newly created implementation or test files after tests pass.
- Silently rolling back files before writing documentation/reports.
- Generating a "PASS" verdict for implementation that was subsequently removed from disk.

---

## 3. Reporting Integrity
1. Final reports and documentation must accurately describe the **actual physical disk state** at the moment execution finishes.
2. If a report claims a package, service, route, or test exists, that file MUST physically exist with non-zero bytes on disk.
3. If an implementation encounters an issue during execution, agents must **fix forward** by editing the implementation rather than reverting the repository to baseline.

---

## 4. Applicability
This rule applies unconditionally to all future architecture, foundation, feature, refactoring, and test implementation tasks in CampusHub.
