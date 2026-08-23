import json
import os
import sys
import urllib.request
from datetime import datetime

USERNAME = os.environ.get("USERNAME_GH", "eekilinc")
TOKEN = os.environ.get("GITHUB_TOKEN", "")
OUTPUT_DIR = os.environ.get("OUTPUT_DIR", "assets")

API = "https://api.github.com"
GRAPHQL = "https://api.github.com/graphql"

LANG_COLORS = {
    "Python": "#3572A5", "TypeScript": "#3178c6", "JavaScript": "#f1e05a",
    "Dart": "#00B4AB", "PHP": "#4F5D95", "C++": "#f34b7d", "C": "#555555",
    "C#": "#178600", "Java": "#b07219", "HTML": "#e34c26", "CSS": "#563d7c",
    "Go": "#00ADD8", "Rust": "#dea584", "Kotlin": "#A97BFF", "Swift": "#F05138",
    "Shell": "#89e051", "Blade": "#f7523f", "Vue": "#41b883", "Ruby": "#701516",
}
FALLBACK_COLORS = ["#8b5cf6", "#0ea5e9", "#10b981", "#f59e0b", "#ef4444", "#ec4899"]

BG = "#1a1b27"
BORDER = "#31344a"
TEXT = "#c0caf5"
MUTED = "#a9b1d6"
ACCENT = "#70a5fd"
FONT = "'Segoe UI',Helvetica,Arial,sans-serif"


def http_json(url, headers=None, data=None):
    req = urllib.request.Request(url, headers=headers or {}, data=data)
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode("utf-8"))


def gh_rest(path):
    headers = {"Accept": "application/vnd.github+json", "User-Agent": "stats-gen"}
    if TOKEN:
        headers["Authorization"] = f"Bearer {TOKEN}"
    return http_json(f"{API}{path}", headers=headers)


def gh_graphql(query):
    if not TOKEN:
        raise RuntimeError("no token for graphql")
    body = json.dumps({"query": query}).encode("utf-8")
    headers = {
        "Authorization": f"Bearer {TOKEN}",
        "Content-Type": "application/json",
        "User-Agent": "stats-gen",
    }
    result = http_json(GRAPHQL, headers=headers, data=body)
    if "errors" in result:
        raise RuntimeError(result["errors"][0].get("message", "graphql error"))
    return result["data"]


def esc(s):
    return s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def fmt(n):
    return f"{n:,}".replace(",", ".")


def get_profile():
    p = gh_rest(f"/users/{USERNAME}")
    return {"followers": p.get("followers", 0), "repos": p.get("public_repos", 0)}


def get_repos():
    repos = []
    page = 1
    while True:
        batch = gh_rest(f"/users/{USERNAME}/repos?per_page=100&page={page}&sort=updated")
        if not batch:
            break
        repos.extend(batch)
        if len(batch) < 100:
            break
        page += 1
    return [r for r in repos if not r.get("fork")]


def get_search_total(query):
    headers = {"Accept": "application/vnd.github+json", "User-Agent": "stats-gen"}
    if TOKEN:
        headers["Authorization"] = f"Bearer {TOKEN}"
    try:
        import urllib.parse
        q = urllib.parse.quote(query)
        return gh_rest(f"/search/issues?q={q}&per_page=1").get("total_count", 0)
    except Exception:
        return None


def get_calendar():
    query = f'''
    query {{
      user(login: "{USERNAME}") {{
        contributionsCollection {{
          contributionCalendar {{
            totalContributions
            weeks {{
              contributionDays {{ date contributionCount }}
            }}
          }}
        }}
      }}
    }}'''
    data = gh_graphql(query)
    cal = data["user"]["contributionsCollection"]["contributionCalendar"]
    return cal["totalContributions"], [w["contributionDays"] for w in cal["weeks"]]


def collect_stats():
    profile = get_profile()
    repos = get_repos()
    stars = sum(r.get("stargazers_count", 0) for r in repos)
    forks = sum(r.get("forks_count", 0) for r in repos)

    lang_bytes = {}
    for r in repos:
        try:
            langs = gh_rest(f"/repos/{USERNAME}/{r['name']}/languages")
            for k, v in langs.items():
                lang_bytes[k] = lang_bytes.get(k, 0) + v
        except Exception:
            continue

    prs = get_search_total(f"author:{USERNAME} type:pr")
    issues = get_search_total(f"author:{USERNAME} type:issue")

    commits = None
    try:
        commits = gh_rest(f"/search/commits?q=author:{USERNAME}&per_page=1").get("total_count", 0)
    except Exception:
        pass

    total_contrib, weeks = None, None
    try:
        total_contrib, weeks = get_calendar()
    except Exception:
        pass

    return {
        "profile": profile,
        "stars": stars,
        "forks": forks,
        "lang_bytes": lang_bytes,
        "prs": prs,
        "issues": issues,
        "commits": commits,
        "total_contrib": total_contrib,
        "weeks": weeks,
    }


def svg_stats(s):
    rows = [
        ("⭐", "Total Stars", s["stars"]),
        ("📦", "Public Repos", s["profile"]["repos"]),
        ("📝", "Total Commits", s["commits"] if s["commits"] is not None else "N/A"),
        ("🔀", "Total PRs", s["prs"] if s["prs"] is not None else "N/A"),
        ("❗", "Total Issues", s["issues"] if s["issues"] is not None else "N/A"),
        ("👥", "Followers", s["profile"]["followers"]),
    ]
    col_x = [46, 258]
    row_y = [64, 102, 140]
    cells = []
    for i, (icon, label, value) in enumerate(rows):
        x = col_x[i % 2]
        y = row_y[i // 2]
        val = fmt(value) if isinstance(value, int) else str(value)
        cells.append(
            f'<text x="{x}" y="{y}" font-family={FONT!r} font-size="15" fill="{MUTED}">{esc(icon)} {esc(label)}</text>'
            f'<text x="{x + 168}" y="{y}" font-family={FONT!r} font-size="16" font-weight="bold" fill="{ACCENT}" text-anchor="end">{val}</text>'
        )
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="480" height="175" viewBox="0 0 480 175">
  <rect x="1" y="1" width="478" height="173" rx="10" fill="{BG}" stroke="{BORDER}" stroke-width="1"/>
  <text x="30" y="36" font-family={FONT!r} font-size="17" font-weight="bold" fill="{ACCENT}">Ekrem's GitHub Stats</text>
  {''.join(cells)}
</svg>
'''


def svg_langs(s):
    items = sorted(s["lang_bytes"].items(), key=lambda kv: kv[1], reverse=True)[:6]
    total = sum(v for _, v in items) or 1
    bar_w = 420
    x = 30
    segs = []
    legend = []
    y = 78
    for i, (name, size) in enumerate(items):
        pct = size / total * 100
        color = LANG_COLORS.get(name, FALLBACK_COLORS[i % len(FALLBACK_COLORS)])
        w = bar_w * pct / 100
        segs.append(f'<rect x="{x:.1f}" y="52" width="{w:.1f}" height="10" rx="2" fill="{color}"/>')
        x += w
        dot_cx = 40
        ly = y + i * 20
        legend.append(
            f'<circle cx="{dot_cx}" cy="{ly - 5}" r="5" fill="{color}"/>'
            f'<text x="54" y="{ly}" font-family={FONT!r} font-size="14" fill="{TEXT}">{esc(name)}</text>'
            f'<text x="450" y="{ly}" font-family={FONT!r} font-size="14" font-weight="bold" fill="{ACCENT}" text-anchor="end">{pct:.1f}%</text>'
        )
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="480" height="205" viewBox="0 0 480 205">
  <rect x="1" y="1" width="478" height="203" rx="10" fill="{BG}" stroke="{BORDER}" stroke-width="1"/>
  <text x="30" y="36" font-family={FONT!r} font-size="17" font-weight="bold" fill="{ACCENT}">Most Used Languages</text>
  {''.join(segs)}
  {''.join(legend)}
</svg>
'''


def svg_contributions(s):
    if not s["weeks"]:
        raise RuntimeError("calendar unavailable")
    cell, gap, pad_l, pad_t = 11, 3, 34, 40
    weeks = s["weeks"]
    width = pad_l + len(weeks) * (cell + gap) + 14
    height = pad_t + 7 * (cell + gap) + 16
    max_count = max((d["contributionCount"] for w in weeks for d in w), default=1) or 1
    scale = ["#21224a", "#312e81", "#5b32b4", "#7c3aed", "#a855f7"]
    month_labels = ["Oca", "Şub", "Mar", "Nis", "May", "Haz", "Tem", "Ağu", "Eyl", "Eki", "Kas", "Ara"]
    rects = []
    labels = []
    prev_month = None
    for wi, week in enumerate(weeks):
        for di, day in enumerate(week):
            count = day["contributionCount"]
            level = 0 if count == 0 else min(4, 1 + int(count / max_count * 3.999) - (1 if count / max_count <= 0.25 else 0))
            if count > 0:
                ratio = count / max_count
                level = 1 if ratio <= 0.25 else 2 if ratio <= 0.5 else 3 if ratio <= 0.75 else 4
            x = pad_l + wi * (cell + gap)
            y = pad_t + di * (cell + gap)
            rects.append(f'<rect x="{x}" y="{y}" width="{cell}" height="{cell}" rx="2.5" fill="{scale[level]}"/>')
            month = int(day["date"][5:7]) - 1
            if di == 0 and month != prev_month:
                labels.append(
                    f'<text x="{x}" y="{pad_t - 10}" font-family={FONT!r} font-size="11" fill="{MUTED}">{month_labels[month]}</text>'
                )
                prev_month = month
    total = s["total_contrib"] or sum(d["contributionCount"] for w in weeks for d in w)
    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}">
  <rect x="1" y="1" width="{width - 2}" height="{height - 2}" rx="10" fill="{BG}" stroke="{BORDER}" stroke-width="1"/>
  <text x="30" y="26" font-family={FONT!r} font-size="16" font-weight="bold" fill="{ACCENT}">{fmt(total)} contributions in the last year</text>
  {''.join(labels)}
  {''.join(rects)}
  <g transform="translate({width - 118}, {height - 14})" font-family={FONT!r} font-size="11" fill="{MUTED}">
    <text x="-60" y="0">Less</text>
    <rect x="-20" y="-9" width="11" height="11" rx="2.5" fill="{scale[0]}"/>
    <rect x="-6" y="-9" width="11" height="11" rx="2.5" fill="{scale[1]}"/>
    <rect x="8" y="-9" width="11" height="11" rx="2.5" fill="{scale[2]}"/>
    <rect x="22" y="-9" width="11" height="11" rx="2.5" fill="{scale[3]}"/>
    <rect x="36" y="-9" width="11" height="11" rx="2.5" fill="{scale[4]}"/>
    <text x="52" y="0">More</text>
  </g>
</svg>
'''


def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    s = collect_stats()
    outputs = {
        "stats.svg": svg_stats(s),
        "languages.svg": svg_langs(s),
        "contributions.svg": svg_contributions(s),
    }
    for name, content in outputs.items():
        path = os.path.join(OUTPUT_DIR, name)
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"wrote {path} ({len(content)} bytes)")


if __name__ == "__main__":
    main()
