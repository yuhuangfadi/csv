---
layout: default
title: CSV document interoperability
---

# Document interoperability

Depending on your operating system and on the software you are using to read/import your CSV you may need to adjust the CSV document:

- [encoding and BOM presence](/9.0/interoperability/encoding/)
- [rfc4180 field compliance](/9.0/interoperability/rfc4180-field/)

<p class="message-info">Out of the box, <code>League\Csv</code> connections do not alter the CSV document presentation and uses PHP's CSV native functions to read and write CSV records.</p>

In the examples we will be using an existing CSV in ISO-8859-15 charset encoding as a starting point. The code may vary if your CSV document is in a different charset and/or exposes different CSV field formats.