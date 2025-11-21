# Safe Video Cleanup Process
**Last Updated:** November 21, 2025

## ⚠️ Automatic Cleanup is Permanently Disabled

After two data loss incidents (Oct 20 & Nov 21, 2025), all automatic video cleanup functions have been permanently disabled in the plugin.

**Why:** Videos are irreplaceable memorial content. The risk of automated deletion far outweighs the cost of storing orphaned videos.

---

## Manual Cleanup Process (Once Per Year)

If you need to clean up orphaned videos, follow this **safe manual process**:

### Step 1: Generate Orphaned Video Report

```bash
# On each site, run the diagnostic:
cd /var/www/*/htdocs
wp eval-file wp-content/plugins/hk-funeral-notices/bunny-api-deletion-tracker.php

# This creates a CSV with missing videos
# Review the CSV to identify truly orphaned videos
```

### Step 2: Identify Truly Orphaned Videos

A video is truly orphaned if:
- ✅ Not in ANY site's database
- ✅ Older than 12 months
- ✅ Confirmed with funeral home that family doesn't need it
- ✅ Not associated with any active funeral notice

**NEVER delete a video unless ALL criteria are met!**

### Step 3: Manual Deletion via Bunny Dashboard

1. Login to https://dashboard.bunny.net
2. Go to Library 499405
3. Find the specific collection
4. Manually select the video
5. Click delete WITH CONFIRMATION
6. Document what was deleted and why

### Step 4: Document Deletions

Keep a log:
```
Date: 2025-12-01
Deleted Video ID: abc-123-def
Reason: Post deleted 18 months ago, funeral home confirmed no longer needed
Deleted by: [Your name]
Collection: Site X
```

---

## Alternative: Just Don't Delete Anything

**Recommended Approach:** Never delete videos.

**Cost Analysis:**
- Bunny Stream storage: ~$0.005 per GB/month
- Average orphaned videos: ~5-10 per site per year
- Average video size: 100MB
- Annual cost: ~$0.50 per site

**Risk vs Reward:**
- Cost: $10/year for 20 sites
- Risk: Losing irreplaceable family memories

**Verdict:** The $10/year is worth the peace of mind.

---

## Emergency Video Recovery

If videos are accidentally deleted:

1. **Contact Bunny.net Support Immediately**
   - Email: support@bunny.net
   - Mention Library ID: 499405
   - Request recovery from backups
   - They may have 7-30 day retention

2. **Check Server Backups**
   - Some videos may have been in WordPress uploads before being moved to Bunny
   - Check GridPane backups for original upload files

3. **Contact Funeral Homes**
   - Request original video files
   - Most families keep copies

4. **Document the Incident**
   - What was deleted
   - When it was deleted
   - How many videos affected
   - Steps taken for recovery

---

## Plugin Updates

**NEVER re-enable automatic cleanup!**

If future plugin versions try to add automatic cleanup:
- Reject the update
- File an issue
- Keep the safety constant defined

---

## Questions?

**"What about failed uploads cluttering the database?"**
- These are just database rows, harmless
- Can be cleaned up manually via SQL if needed
- No urgency to remove them

**"What about test videos during development?"**
- Delete manually via Bunny dashboard
- Or keep them - storage is cheap

**"What if a funeral notice is deleted?"**
- The `before_delete_post` hook still works
- Video is deleted when post is deleted
- This is safe because it's user-initiated, not automatic

---

## Summary

**Old Approach:** Automated cron deletes orphaned videos → Data loss incidents
**New Approach:** Manual review only, if at all → Safe, no surprises

Videos are precious. Automation is dangerous. Manual is safe.
