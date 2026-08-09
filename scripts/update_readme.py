import os
import requests
from datetime import datetime


USERNAME = os.environ["eekilinc"]

README_FILE = "README.md"

START_MARKER = "<!-- REPOSITORIES:START -->"
END_MARKER = "<!-- REPOSITORIES:END -->"

MAX_REPOSITORIES = 6


def get_repositories():
    url = f"https://api.github.com/users/{USERNAME}/repos"

    params = {
        "per_page": 100,
        "sort": "updated",
        "direction": "desc"
    }

    response = requests.get(url, params=params, timeout=30)
    response.raise_for_status()

    repositories = response.json()

    return [
        repo
        for repo in repositories
        if not repo["fork"]
        and not repo["archived"]
        and not repo["private"]
    ]


def get_languages(repo):
    url = repo["languages_url"]

    response = requests.get(url, timeout=30)

    if response.status_code != 200:
        return []

    languages = response.json()

    if not languages:
        return []

    total = sum(languages.values())

    result = []

    for language, value in sorted(
        languages.items(),
        key=lambda x: x[1],
        reverse=True
    ):

        percentage = (value / total) * 100

        if percentage >= 5:
            result.append(language)

    return result[:4]


def create_repository_block(repo):
    name = repo["name"]
    description = repo["description"] or "No description available."

    if len(description) > 100:
        description = description[:97] + "..."

    languages = get_languages(repo)

    language_text = ""

    if languages:
        language_text = " · ".join(
            f"`{language}`"
            for language in languages
        )

    stars = repo["stargazers_count"]
    forks = repo["forks_count"]

    updated = datetime.fromisoformat(
        repo["updated_at"].replace("Z", "+00:00")
    )

    updated_text = updated.strftime("%Y-%m-%d")

    return f"""
#### [{name}]({repo["html_url"]})

{description}

{language_text}

⭐ {stars} · 🍴 {forks} · Updated {updated_text}

"""


def generate_repositories():
    repositories = get_repositories()

    repositories = repositories[:MAX_REPOSITORIES]

    content = "\n"

    for repo in repositories:
        content += create_repository_block(repo)

    return content


def update_readme():

    with open(README_FILE, "r", encoding="utf-8") as file:
        readme = file.read()

    start = readme.find(START_MARKER)
    end = readme.find(END_MARKER)

    if start == -1 or end == -1:
        raise RuntimeError(
            "Repository markers were not found in README.md"
        )

    start_content = start + len(START_MARKER)

    repositories = generate_repositories()

    new_readme = (
        readme[:start_content]
        + repositories
        + readme[end:]
    )

    with open(README_FILE, "w", encoding="utf-8") as file:
        file.write(new_readme)

    print("README updated successfully.")


if __name__ == "__main__":
    update_readme()
