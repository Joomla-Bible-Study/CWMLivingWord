5.8.0 stops LivingWord sending readers to BibleGateway, and makes scripture text and audio work for chapter ranges and comma-listed readings. It also bundles CWM Scripture 1.2.20, which fixes a security problem. Please update.

## Security

- **Bundles CWM Scripture 1.2.20, which fixes a security problem.** In earlier versions of the scripture package, anyone visiting your site could use its translation tools without logging in. A visitor could delete your downloaded Bible translations (all except KJV and WEB), or keep starting large downloads. 1.2.20 limits those tools to administrators who can manage plugins. Earlier LivingWord releases, including 5.8.0-rc1, bundle affected versions. Details are in [GHSA-g2v4-h5q3-xcwh](https://github.com/Joomla-Bible-Study/CWMScriptureLinks/security/advisories/GHSA-g2v4-h5q3-xcwh).
  - If you can't update LivingWord straight away, update the CWM Scripture package on its own from Joomla's updater.

## Changes

- **No more BibleGateway links.** LivingWord no longer links to BibleGateway or any other external Bible site. When scripture text can't be shown, a reading appears as its plain reference, such as "Genesis 1-3". That happens when the CWM Scripture library is missing, the display mode is Reference Only, or no provider answered. The plan view's day list is plain text for the same reason.
- **"Link Only (BibleGateway)" is now "Reference Only (no scripture text)".** The setting keeps its saved value, so there's nothing to re-save. The daily-reading module's "Show Reading Link" option is now "Show Scripture Text", which is what it has always switched.
- **Bundles CWM Scripture 1.2.20**, with library 1.1.26. Besides the security fix, it brings the GetBible fix described under Fixes, and makes scripture tooltips in articles work on sites without CWM Proclaim.
- **Registers with the scripture library from the first install.** LivingWord now tells the scripture library it depends on it through the library's own install entry point. The library then knows from the moment LivingWord is installed, not only after its next update, and refuses to be removed while LivingWord needs it.

## Fixes

- **Scripture text for chapter ranges and comma lists.** Readings like "Ecclesiastes 1-3", "Romans 5-8,12" and "Luke 1:26-56,2:1-7" now show their text.
  - LivingWord used to hand plan readings to the scripture library exactly as written, and no provider reads that shorthand. Comma-listed readings got no text at all, and a chapter range came back as its first three verses from translations stored on your site.
  - Each reading is now split into plain references before it's looked up.
- **Whole chapters from GetBible.net.** A chapter from a GetBible-served translation returned only its first verse, and a passage crossing chapters returned nothing. The bundled scripture library fixes this.
- **A passage that can't be fetched no longer disappears.** On days with several passages, one that couldn't be fetched was silently left out. It now shows its reference.
- **Audio covers the whole reading.** "Romans 5-8,12" plays chapters 5 to 8 and 12; it played only chapter 5. "Philippians 2,4" plays both chapters instead of nothing, and "2 Timothy 3:10-4:22" includes chapter 4.
- **The "Show Scripture Text" button is translated.** It was in English on every site.
- **Warning-coloured text is readable.** Three things used Bootstrap's light yellow, which fails the WCAG contrast minimum: the dashboard's count of inactive subscribers, partly read days in the calendar plan view, and the audio-test messages on the plan form. They now use the darker warning colour.

## Notes

- Requires PHP 8.3 or later, and Joomla 5 or 6.
- No database changes. Reading plans, progress, notes and groups are untouched.
- Whole-chapter readings from translations served by API.Bible still show only their reference. That's a scripture library limitation still to be fixed.
- For developers: `CwmscriptureHelper::buildBibleGatewayLink()` and `getBibleGatewayUrl()` are removed. `buildReadingLink()` still works but is deprecated in favour of `buildReadingReference()`. A template override that calls a removed method needs updating.
