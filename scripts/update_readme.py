import json
import os
import sys
import time
import urllib.parse
import urllib.request
from datetime import datetime

USERNAME = os.environ.get("GITHUB_USERNAME", "eekilinc")
TOKEN = os.environ.get("GITHUB_TOKEN", "")
README_FILE = "README.md"
START_MARKER = "<!-- REPOSITORIES:START -->"
END_MARKER = "<!-- REPOSITORIES:END -->"
MAX_REPOSITORIES = 6

API = "https://api.github.com"

# Curated priority order for showcased projects
PRIORITY_REPOS = [
    "indirgitsin",
    "MyFinans",
    "EzanApp",
    "ocr-capture",
    "Postaci",
    "eekilinc.github.io",
]

# Curated descriptions for repos that might miss a GitHub description
REPO_DESCRIPTIONS = {
    "indirgitsin": "⚡ Android audio & video download manager built with Kotlin & Jetpack Compose. Parallel streams & ffmpeg integration.",
    "MyFinans": "💰 Personal finance & budget tracker built with React, Capacitor (Mobile/Web), and Node.js backend.",
    "EzanApp": "🕌 Islamic prayer times & customizable reminder mobile app engineered with Flutter, C++, and Swift.",
    "ocr-capture": "🔍 High-performance screen OCR text extractor tool built with TypeScript, Rust & Web technologies.",
    "Postaci": "📮 Fast & lightweight API request tester and HTTP client utility built with TypeScript.",
    "eekilinc.github.io": "🌐 Personal developer portfolio and blog showcasing engineering projects & research.",
}

_lang_cache = {}


def log(msg):
    print(msg, file=sys.stderr, flush=True)


def auth_headers():
    headers = {
        "Accept": "application/vnd.github+json",
        "User-Agent": "readme-updater",
        "X-GitHub-Api-Version": "2022-11-28",
    }
    if TOKEN:
        headers["Authorization"] = f"Bearer {TOKEN}"
    else:
        log("warning: GITHUB_TOKEN not set, using unauthenticated API (60 req/hour limit)")
    return headers


def http_get_json(url, timeout=30, retries=3):
    """GET JSON with retry on 429/5xx. Raises on final failure."""
    last_err = None
    for attempt in range(1, retries + 1):
        req = urllib.request.Request(url, headers=auth_headers())
        try:
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                return json.loads(resp.read().decode("utf-8"))
        except urllib.error.HTTPError as e:
            last_err = e
            try:
                body = e.read().decode("utf-8", errors="replace")[:300]
            except Exception:
                body = ""
            log(f"warning: GET {url} attempt {attempt}/{retries} HTTP {e.code}: {body}")
            # Respect rate-limit headers
            if e.code == 403:
                reset = e.headers.get("X-RateLimit-Reset")
                if reset:
                    try:
                        wait = max(0, int(reset) - int(time.time()) + 5)
                        log(f"warning: rate-limited, resets in {wait}s")
                        if attempt == retries or wait > 120:
                            raise
                    except (ValueError, TypeError):
                        pass
            retry_after = e.headers.get("Retry-After") if e.headers else None
            if e.code in (429, 500, 502, 503, 504) and attempt < retries:
                wait = int(retry_after) if retry_after and str(retry_after).isdigit() else 2 ** attempt
                time.sleep(min(wait, 30))
                continue
            raise
        except (urllib.error.URLError, TimeoutError, OSError) as e:
            last_err = e
            log(f"warning: GET {url} attempt {attempt}/{retries} network error: {e}")
            if attempt < retries:
                time.sleep(2 ** attempt)
                continue
            raise
    raise RuntimeError(f"GET {url} failed after {retries} attempts: {last_err}")


def get_repositories():
    params = {
        "per_page": "100",
        "sort": "updated",
        "direction": "desc",
    }
    url = f"{API}/users/{USERNAME}/repos?{urllib.parse.urlencode(params)}"

    try:
        repositories = http_get_json(url)
    except Exception as e:
        log(f"error: fetching repos from GitHub API failed: {e}")
        raise SystemExit(1)

    if not isinstance(repositories, list):
        log(f"error: unexpected API response: {repositories!r}"[:500])
        raise SystemExit(1)

    valid_repos = [
        repo
        for repo in repositories
        if not repo.get("fork", False)
        and not repo.get("archived", False)
        and not repo.get("private", False)
        and repo.get("name") != USERNAME
    ]

    # Re-order: Priority repos first, remaining sorted by stars then recency
    repo_map = {str(r.get("name", "")).lower(): r for r in valid_repos}
    ordered = []

    for name in PRIORITY_REPOS:
        key = name.lower()
        if key in repo_map:
            ordered.append(repo_map.pop(key))

    remaining = sorted(
        repo_map.values(),
        key=lambda item: (
            item.get("stargazers_count", 0),
            item.get("updated_at", ""),
        ),
        reverse=True,
    )
    ordered.extend(remaining)

    return ordered


def get_languages(repo):
    full_name = repo.get("full_name", "")
    if full_name in _lang_cache:
        return _lang_cache[full_name]

    languages_url = repo.get("languages_url")
    if not languages_url:
        fallback = [repo["language"]] if repo.get("language") else []
        _lang_cache[full_name] = fallback
        return fallback

    try:
        # Small delay to be gentle with secondary rate limits
        time.sleep(0.3)
        languages = http_get_json(languages_url, retries=2)
        result = sorted(languages.items(), key=lambda item: item[1], reverse=True)
        result = [language for language, _ in result[:4]]
        _lang_cache[full_name] = result
        return result
    except Exception as e:
        log(f"warning: languages fetch failed for {full_name}: {e}")
        fallback = [repo["language"]] if repo.get("language") else []
        _lang_cache[full_name] = fallback
        return fallback


def format_updated(repo):
    raw = repo.get("updated_at", "")
    try:
        dt = datetime.strptime(raw, "%Y-%m-%dT%H:%M:%SZ")
        return dt.strftime("%Y-%m-%d")
    except (ValueError, TypeError):
        return ""


def create_card(repo):
    name = repo.get("name", "unknown")
    url = repo.get("html_url", "#")

    # Use curated description if repo description is empty, missing, or generic
    raw_desc = (repo.get("description") or "").strip()
    lower_desc_map = {k.lower(): v for k, v in REPO_DESCRIPTIONS.items()}
    if not raw_desc or raw_desc.lower() in ["no description", "no description.", "none", "null"]:
        description = REPO_DESCRIPTIONS.get(name) or lower_desc_map.get(name.lower(), "Modern software engineering project.")
    else:
        description = raw_desc

    # Basic HTML escaping (repo descriptions are user-controlled)
    description = (
        description.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    )

    if len(description) > 115:
        description = description[:112] + "..."

    languages = get_languages(repo)
    if languages:
        language_text = " · ".join(
            f"<code>{language}</code>" for language in languages
        )
    else:
        primary_lang = repo.get("language")
        language_text = f"<code>{primary_lang}</code>" if primary_lang else "<code>Code</code>"

    stars = repo.get("stargazers_count", 0)
    forks = repo.get("forks_count", 0)
    updated = format_updated(repo)
    meta_bits = []
    # Only show stars/forks when socially meaningful; otherwise show freshness
    if stars > 0:
        meta_bits.append(f"⭐ <b>{stars}</b>")
    if forks > 0:
        meta_bits.append(f"🍴 <b>{forks}</b>")
    if updated:
        meta_bits.append(f"🕒 <b>{updated}</b>")
    if not meta_bits:
        meta_bits.append("🕒 <b>active</b>")
    meta = " &nbsp; · &nbsp; ".join(meta_bits)

    # Intelligent badge icon detection by topic, language, and name
    # NOTE: use word-ish matching; plain "ai" substring causes false
    # positives (e.g. "email" contains "ai"), so it is intentionally excluded.
    search_str = f"{name} {description} {' '.join(languages)}".lower()
    if any(k in search_str for k in ["finans", "finance", "budget", "money"]):
        icon = "💰"
    elif any(k in search_str for k in ["ezan", "prayer", "islamic"]):
        icon = "🕌"
    elif any(k in search_str for k in ["postaci", "postman", "insomnia", "http client", "api tester", "api client"]):
        icon = "📮"
    elif any(k in search_str for k in ["ocr", "vision", "opencv", "tesseract", "openai", "deep learning", "tensorflow", "pytorch"]):
        icon = "🤖"
    elif any(k in search_str for k in ["indir", "download", "media", "stream"]):
        icon = "📥"
    elif any(k in search_str for k in ["flutter", "kotlin", "android", "compose", "swift", "ios"]):
        icon = "📱"
    elif any(k in search_str for k in ["arduino", "robot", "ros", "iot", "sensor", "hardware"]):
        icon = "🔌"
    elif any(k in search_str for k in ["portfolio", "github.io", "web", "react"]):
        icon = "🌐"
    else:
        icon = "⚡"

    return f"""<td width="50%" valign="top">

<h4><a href="{url}">{icon} <b>{name}</b></a></h4>

<p>{description}</p>

<p>{language_text}</p>

<p>{meta} &nbsp; · &nbsp; <a href="{url}"><b>Explore Code →</b></a></p>

</td>"""


def generate_repository_section():
    repositories = get_repositories()[:MAX_REPOSITORIES]

    if not repositories:
        # Never wipe the showcase with an empty table: abort instead.
        log("error: no repositories to showcase, aborting to protect README")
        raise SystemExit(1)

    rows = []

    for i in range(0, len(repositories), 2):
        left = create_card(repositories[i])

        if i + 1 < len(repositories):
            right = create_card(repositories[i + 1])
        else:
            right = '<td width="50%"></td>'

        rows.append(f"<tr>\n{left}\n{right}\n</tr>")

    return (
        "<table>\n"
        "<tbody>\n"
        + "\n".join(rows)
        + "\n</tbody>\n"
        "</table>"
    )


def update_readme():
    if not os.path.exists(README_FILE):
        log(f"error: {README_FILE} does not exist.")
        raise SystemExit(1)

    with open(README_FILE, "r", encoding="utf-8") as file:
        readme = file.read()

    start = readme.find(START_MARKER)
    end = readme.find(END_MARKER)

    if start == -1 or end == -1:
        raise RuntimeError("REPOSITORIES markers not found in README.md")

    if end < start:
        raise RuntimeError("REPOSITORIES markers are in the wrong order")

    start_position = start + len(START_MARKER)
    repository_section = generate_repository_section()

    new_readme = (
        readme[:start_position]
        + "\n"
        + repository_section
        + "\n"
        + readme[end:]
    )

    with open(README_FILE, "w", encoding="utf-8") as file:
        file.write(new_readme)

    print(f"README updated successfully for @{USERNAME}")


if __name__ == "__main__":
    try:
        import urllib.error  # noqa: F401  (ensures error class available for http_get_json)
    except Exception:
        pass
    update_readme()
