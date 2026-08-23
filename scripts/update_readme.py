import os
import requests

USERNAME = os.environ["GITHUB_USERNAME"]
README_FILE = "README.md"
START_MARKER = "<!-- REPOSITORIES:START -->"
END_MARKER = "<!-- REPOSITORIES:END -->"
MAX_REPOSITORIES = 6


def get_repositories():
    url = f"https://api.github.com/users/{USERNAME}/repos"
    params = {
        "per_page": 100,
        "sort": "updated",
        "direction": "desc",
    }

    response = requests.get(url, params=params, timeout=30)
    response.raise_for_status()

    repositories = response.json()

    return [
        repo
        for repo in repositories
        if not repo.get("fork", False)
        and not repo.get("archived", False)
        and not repo.get("private", False)
        and repo["name"] != USERNAME
    ]


def get_languages(repo):
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


def create_card(repo):
    name = repo["name"]
    url = repo["html_url"]
    description = repo.get("description") or "No description."

    if len(description) > 100:
        description = description[:97] + "..."

    languages = get_languages(repo)

    if languages:
        language_text = " · ".join(
            f"<code>{language}</code>" for language in languages
        )
    else:
        language_text = ""

    stars = repo.get("stargazers_count", 0)
    forks = repo.get("forks_count", 0)

    return f"""
<td width="50%" valign="top">

<h3><a href="{url}">🚀 {name}</a></h3>

<p>{description}</p>

<p>{language_text}</p>

<p>⭐ {stars} &nbsp; · &nbsp; 🍴 {forks}</p>

</td>
"""


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

        rows.append(f"<tr>{left}{right}</tr>")

    return (
        "<table>\n"
        "<tbody>\n"
        + "\n".join(rows)
        + "\n</tbody>\n"
        "</table>"
    )


def update_readme():
    with open(README_FILE, "r", encoding="utf-8") as file:
        readme = file.read()

    start = readme.find(START_MARKER)
    end = readme.find(END_MARKER)

    if start == -1 or end == -1:
        raise RuntimeError(
            "REPOSITORIES markers not found in README.md"
        )

    if end < start:
        raise RuntimeError(
            "REPOSITORIES markers are in the wrong order"
        )

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
