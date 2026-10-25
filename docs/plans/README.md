# Plans

Plans for larger changes, one file per plan. Each plan has a status line at the top and a status per item. Update it in the same pull request as the work.

| Plan | Status | Still open |
|---|---|---|
| [Fix plan for the 2026-10-05 review](fix-plan-2026-10-05.md) | done (PRs #9 to #17) | nothing |
| [Start location for invitations](plan-start-location-2026-10-07.md) | done (PRs #19, #23) | later: map link, pre-filling the location |
| [Structured data for invitations, blogs and chronicles](plan-event-structured-data-2026-10-07.md) | done (PRs #20, #21) | `og:image`; later: `Event` markup on chronicles and the plan |
| [Fix plan for the 2026-10-11 security review](fix-plan-2026-10-11.md) | done (PRs #30 to #33) | later: full script CSP, deleting orphaned routes |
| [Calendar export of invitations](plan-ics-export-2026-10-14.md) | done (PR #34) | optional "Google Kalendár" link |

Related documents:

- [Production deploy checklist](../deploy-checklist.md): manual production steps that merged pull requests still need.
- [Decision: GA4 through gtag.js or Google Tag Manager](../decisions/ga4-vs-gtm.md): open, blocks the Google Analytics part of the next deploy.
- [Code review of 2026-10-05](../reviews/code-review-2026-10-05.md): the findings behind the first fix plan.
