#!/usr/bin/env python3
from __future__ import annotations

import json
import re
from pathlib import Path

import requests
from bs4 import BeautifulSoup

OUT = Path("validation-out/municipality-results")
OUT.mkdir(parents=True, exist_ok=True)
BASE = "https://www.oesterreich.gv.at"
URLS = {
    "picker-eisenstadt": f"{BASE}/de/orgsearch/gemeindeauswahl/orgtypegroup/2?q=Eisenstadt",
    "picker-gkz": f"{BASE}/de/orgsearch/gemeindeauswahl/orgtypegroup/2?q=10101",
    "group": f"{BASE}/de/orgsearch/orgtypegroup/2?gkz=10101",
    "type": f"{BASE}/de/orgsearch/orgtyp/10?gkz=10101",
    "group-node": f"{BASE}/orgsearch/orgtypegroup/2?gkz=10101",
    "type-node": f"{BASE}/orgsearch/orgtyp/10?gkz=10101",
}

session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/126 Safari/537.36",
    "Accept-Language": "de-AT,de;q=0.9,en;q=0.5",
})
summary: dict[str, object] = {}
for key, url in URLS.items():
    item: dict[str, object] = {"requested_url": url}
    try:
        response = session.get(url, timeout=60, allow_redirects=True)
        item.update({
            "status": response.status_code,
            "final_url": response.url,
            "size": len(response.content),
            "content_type": response.headers.get("content-type"),
        })
        (OUT / f"{key}.html").write_text(response.text, encoding="utf-8")
        soup = BeautifulSoup(response.text, "lxml")
        item["title"] = soup.title.get_text(" ", strip=True) if soup.title else ""
        item["h1"] = [h.get_text(" ", strip=True) for h in soup.find_all("h1")]
        item["h2"] = [h.get_text(" ", strip=True) for h in soup.find_all("h2")]
        item["h3"] = [h.get_text(" ", strip=True) for h in soup.find_all("h3")]
        item["external_links"] = [
            {"text": a.get_text(" ", strip=True), "href": a.get("href")}
            for a in soup.find_all("a", href=True)
            if str(a.get("href")).startswith(("http://", "https://"))
        ]
        item["gkz_objects"] = []
        for match in re.finditer(r'\\?"gkz\\?"\s*:\s*\\?"?([0-9]{5})', response.text):
            start = max(0, match.start() - 300)
            item["gkz_objects"].append(response.text[start:match.start() + 900])
            if len(item["gkz_objects"]) >= 20:
                break
        item["homepage_snippets"] = []
        for term in ["Homepage", "Internet", "Eisenstadt", "vs-eisenstadt", "rathaus"]:
            matches = []
            for match in list(re.finditer(term, response.text, re.I))[:10]:
                matches.append(response.text[max(0, match.start() - 250):match.start() + 700])
            item["homepage_snippets"].append({"term": term, "matches": matches})
    except Exception as exc:  # noqa: BLE001
        item["error"] = repr(exc)
    summary[key] = item

(OUT / "summary.json").write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")
print(json.dumps({key: {
    "status": value.get("status"),
    "final_url": value.get("final_url"),
    "h1": value.get("h1"),
    "h2": value.get("h2"),
    "h3": value.get("h3"),
    "external_links": value.get("external_links"),
    "gkz_count": len(value.get("gkz_objects", [])),
} for key, value in summary.items()}, ensure_ascii=False, indent=2))
