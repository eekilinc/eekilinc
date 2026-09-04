import os
import requests

USERNAME = os.environ.get("GITHUB_USERNAME", "eekilinc")
README_FILE = "README.md"
START_MARKER = "<!-- REPOSITORIES:START -->"
END_MARKER = "<!-- REPOSITORIES:END -->"
MAX_REPOSITORIES = 6

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


def get_repositories():
    url = f"https://api.github.com/users/{USERNAME}/repos"
    params = {
        "per_page": 100,
        "sort": "updated",
        "direction": "desc",
    }

    try:
        response = requests.get(url, params=params, timeout=30)
        response.raise_for_status()
        repositories = response.json()
    except Exception as e:
        print(f"Warning fetching repos from GitHub API: {e}")
        return []

    valid_repos = [
        repo
        for repo in repositories
        if not repo.get("fork", False)
        and not repo.get("archived", False)
        and not repo.get("private", False)
        and repo["name"] != USERNAME
    ]

    # Re-order: Priority repos first, followed by most recently updated
    repo_map = {r["name"].lower(): r for r in valid_repos}
    ordered = []

    for name in PRIORITY_REPOS:
        for r_name, r_obj in list(repo_map.items()):
            if r_name == name.lower():
                ordered.append(r_obj)
                del repo_map[r_name]
                break

    # Append remaining repos sorted by updated_at
    remaining = sorted(
        repo_map.values(),
        key=lambda item: item.get("updated_at", ""),
        reverse=True,
    )
    ordered.extend(remaining)

    return ordered


def get_languages(repo):
    if "languages_url" not in repo:
        return []

    try:
        response = requests.get(repo["languages_url"], timeout=30)
        if response.status_code != 200:
            return []
        languages = response.json()
        return [
            language
            for language, _ in sorted(
                languages.items(),
                key=lambda item: item[1],
                reverse=True,
            )[:4]
        ]
    except Exception:
        return []


def create_card(repo):
    name = repo["name"]
    url = repo["html_url"]
    
    # Use curated description if repo description is empty, missing, or generic
    raw_desc = (repo.get("description") or "").strip()
    if not raw_desc or raw_desc.lower() in ["no description", "no description.", "none", "null"]:
        description = REPO_DESCRIPTIONS.get(name, "Modern software engineering project.")
    else:
        description = raw_desc

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

    # Intelligent badge icon detection by topic, language, and name
    search_str = f"{name} {description} {' '.join(languages)}".lower()
    if any(k in search_str for k in ["finans", "finance", "budget", "money"]):
        icon = "💰"
    elif any(k in search_str for k in ["ezan", "prayer", "islamic"]):
        icon = "🕌"
    elif any(k in search_str for k in ["ocr", "vision", "opencv", "ai", "model", "deep", "tensorflow", "pytorch"]):
        icon = "🤖"
    elif any(k in search_str for k in ["indir", "download", "media", "stream"]):
        icon = "📥"
    elif any(k in search_str for k in ["postaci", "http", "api", "request", "client"]):
        icon = "📮"
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

<p>⭐ <b>{stars}</b> &nbsp; · &nbsp; 🍴 <b>{forks}</b> &nbsp; · &nbsp; <a href="{url}"><b>Explore Code →</b></a></p>

</td>"""


def generate_repository_section():
    repositories = get_repositories()[:MAX_REPOSITORIES]

    if not repositories:
        return "<p>No repositories found.</p>"

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
        print(f"Error: {README_FILE} does not exist.")
        return

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
    update_readme()
