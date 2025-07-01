# Lost Images Checker

This small helper shows you how to check your Piwigo gallery for any potentially missing original images that might have been accidentally deleted due to a bug in the “Overwrite existing thumbnails” function **in all versions up to 0.3.12.1**.  

## Background

**Critical bug:** In versions up to **AlbumPilot 0.3.12.1**, there was a severe issue in Step 4 (thumbnail generation) that could accidentally delete original source image files if the “Overwrite existing thumbnails” option was enabled.  
This affected original images whose dimensions exactly matched certain configured derivative (thumbnail) sizes.  

In **version 0.3.13**, multiple safety guards were added to fully fix this issue and make sure that only actual derivative thumbnails can ever be deleted.  

**Important:** If you ever ran Step 4 with the overwrite option enabled using an older version (0.3.12.1 or earlier), it is strongly recommended to double-check your albums to ensure that no original photos were unintentionally removed.

## What you should do

This folder provides you with:

- An example SQL snippet to help you list potentially affected images in **phpMyAdmin**. Please adjust the dimensions to match your **actual configured derivative sizes**, as these vary between installations!
- A small shell script (`find_lost_images.sh`) to compare the exported list with files on disk.
- Step-by-step instructions on how to run this check.

If you discover missing original files, please restore them from your backup.

Again, apologies for this oversight - despite careful testing, this edge case slipped through. Please excuse any inconvenience caused.

---

## Where to find your standard image sizes in Piwigo

You can view and adjust your gallery’s configured image sizes in the **Piwigo Admin Panel** under:  
**Configuration → Options → Photo Sizes**  
Check here to see which derivative sizes you use. The default sizes are typically:

```
square      120x120
thumbnail   144x144
tiny        240x240
xxsmall     432x324
xsmall      576x432
small       792x594
medium      1008x756
large       1920x1080
xlarge      3840x2400
```

---

## SQL to find suspicious images

Use the following SQL query in phpMyAdmin to list all images with dimensions that exactly match your defined derivative sizes:

**Important:** The example list of sizes below must be adapted to your own Piwigo configuration! These dimensions are just an example and will probably not match your setup.  
Especially the last two sizes (`1920x1080` and `3840x2400`) differ from Piwigo’s default and must be changed to your own standard dimensions if needed.  
If you do not adjust them, you might miss affected images!


```sql
SELECT
  id,
  file,
  path,
  width,
  height,
  filesize
FROM
  piwigo_images
WHERE
  (width = 120 AND height = 120)
  OR (width = 144 AND height = 144)
  OR (width = 240 AND height = 240)
  OR (width = 432 AND height = 324)
  OR (width = 576 AND height = 432)
  OR (width = 792 AND height = 594)
  OR (width = 1008 AND height = 756)
  OR (width = 1920 AND height = 1080)
  OR (width = 3840 AND height = 2400)
ORDER BY
  id ASC;
```

---

## How to export the results

1. Run the SQL in phpMyAdmin.
2. Click **Export** → Select **CSV** format.
3. Download the `.csv` file.  
   This should contain your potentially affected images.

---

## How to run the check script

This plugin includes a simple shell script to verify which images exist on disk:

1. Place your exported CSV in this directory as `piwigo_images.csv`.  
2. Adjust the `ROOT_PATH` in `find_lost_images.sh` to match your Piwigo root folder.
3. Make the script executable:  
   ```bash
   chmod +x find_lost_images.sh
   ```
4. Run it:  
   ```bash
   ./find_lost_images.sh
   ```

The script will loop through each image in the CSV, check if it exists, and log `[FOUND]` or `[MISSING]` for each file.

---

## Restore missing images

If any files show up as `[MISSING]`, you should restore them from your backup.

---
