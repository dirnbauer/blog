#!/usr/bin/env python3
from __future__ import annotations

import json
import re
import urllib.parse
from pathlib import Path

import requests
from bs4 import BeautifulSoup

OUT = Path("validation-out/municipality")
OUT.mkdir(parents=True, exist_ok=True)
URL = "https://www.oesterreich.gv.at/de/orgsearch/gemeindeauswahl/orgtypegroup/2"

session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/126 Safari/537.36",
    "Accept-Language": "de-AT,de;q=0.9,en;q=0.5",
})
response = session.get(URL, timeout=60)
response.raise_for_status()
(OUT / "picker.html").write_text(response.text, encoding="utf-8")
soup = BeautifulSoup(response.text, "lxml")
summary: dict[str, object] = {
    "status": response.status_code,
    "url": response.url,
    "forms": [],
    "controls": [],
    "links": [],
    "scripts": [],
    "html_snippets": {},
}
for form in soup.find_all("form"):
    summary["forms"].append({
        "action": form.get("action"),
        "method": form.get("method"),
        "text": " ".join(form.stripped_strings)[:1000],
    })
for control in soup.find_all(["input", "button", "select"]):
    summary["controls"].append({
        "tag": control.name,
        "name": control.get("name"),
        "id": control.get("id"),
        "type": control.get("type"),
        "value": control.get("value"),
        "aria": control.get("aria-label"),
        "text": " ".join(control.stripped_strings)[:200],
    })
for anchor in soup.find_all("a", href=True):
    summary["links"].append({
        "text": " ".join(anchor.stripped_strings),
        "href": anchor.get("href"),
    })

terms = [
    "gemeinde", "region", "autocomplete", "suggest", "orgtypegroup",
    "regionSelection", "api/", "search", "postal", "municipality",
]
for term in terms:
    matches = []
    for match in list(re.finditer(term, response.text, re.I))[:40]:
        matches.append(
            response.text[max(0, match.start() - 300):match.start() + 900].replace("\n", " ")
        )
    summary["html_snippets"][term] = matches

for index, script in enumerate(soup.find_all("script", src=True)):
    src = urllib.parse.urljoin(URL, script.get("src"))
    item: dict[str, object] = {
        "src": src,
        "status": None,
        "size": 0,
        "matched_terms": [],
        "snippets": {},
    }
    try:
        script_response = session.get(src, timeout=60)
        item["status"] = script_response.status_code
        item["size"] = len(script_response.content)
        if script_response.ok:
            text = script_response.text
            matched = [term for term in terms if term.casefold() in text.casefold()]
            item["matched_terms"] = matched
            if matched:
                safe_name = re.sub(
                    r"[^A-Za-z0-9._-]", "_", urllib.parse.urlsplit(src).path
                )[-140:]
                (OUT / f"script-{index:02d}-{safe_name}.js").write_text(text, encoding="utf-8")
                snippets: dict[str, list[str]] = {}
                for term in matched:
                    snippets[term] = [
                        text[max(0, match.start() - 400):match.start() + 1200]
                        for match in list(re.finditer(term, text, re.I))[:30]
                    ]
                item["snippets"] = snippets
    except Exception as exc:  # noqa: BLE001
        item["error"] = repr(exc)
    summary["scripts"].append(item)

(OUT / "summary.json").write_text(
    json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8"
)
print(json.dumps({
    "status": summary["status"],
    "forms": summary["forms"],
    "controls": summary["controls"],
    "matching_scripts": [
        {
            "src": item["src"],
            "size": item["size"],
            "matched_terms": item["matched_terms"],
        }
        for item in summary["scripts"]
        if item.get("matched_terms")
    ],
}, ensure_ascii=False, indent=2))
