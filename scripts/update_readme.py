```python
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
        "direction": "desc"
    }

    response = requests.get(
        url,
        params=params,
        timeout=30
    )

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
    response = requests.get(
        repo["languages_url"],
        timeout=30
    )

    if response.status_code != 200:
        return []

    languages = response.json()

    if not languages:
        return []

    languages = sorted(
        languages.items(),
        key=lambda item: item[1],
        reverse=True
    )

    return [
        language
        for language, _ in languages[:4]
    ]


def create_card(repo):
    name = repo["name"]
    url = repo["html_url"]

    description = repo["description"] or "No description."

    if len(description) > 100:
        description = description[:97] + "..."

    languages = get_languages(repo)

    if languages:
        language_text = " · ".join(
            f"<code>{language}</code>"
            for language in languages
        )
    else:
        language_text = ""

    stars = repo["stargazers_count"]
    forks = repo["forks_count"]

    return f"""
<td width="50%" valign="top">

<h3>
<a href="{url}">🚀 {name}</a>
</h3>

<p>{description}</p>

<p>
{language_text}
</p>

<p>
⭐ {stars} &nbsp; · &nbsp; 🍴 {forks}
</p>

</td>
"""


def generate_repository_section():
    repositories = get_repositories()

    repositories = repositories[:MAX_REPOSITORIES]

    rows = []

    for i in range(0, len(repositories), 2):

        left = create_card(repositories[i])

        if i + 1 < len(repositories):
            right = create_card(
                repositories[i + 1]
            )
        else:
            right = '<td width="50%"></td>'

        rows.append(
            f"""
<tr>
{left}
{right}
</tr>
"""
        )

    return f"""
<table>
<tbody>
{''.join(rows)}
</tbody>
</table>
"""


def update_readme():

    with open(
        README_FILE,
        "r",
        encoding="utf-8"
    ) as file:
        readme = file.read()

    start = readme.find(START_MARKER)
    end = readme.find(END_MARKER)

    if start == -1:
        raise Exception(
            "REPOSITORIES:START marker not found."
        )

    if end == -1:
        raise Exception(
            "REPOSITORIES:END marker not found."
        )

    if end < start:
        raise Exception(
            "Repository markers are in the wrong order."
        )

    repository_section = generate_repository_section()

    start_position = start + len(START_MARKER)

    new_readme = (
        readme[:start_position]
        + "\n"
        + repository_section
        + "\n"
        + readme[end:]
    )

    with open(
        README_FILE,
        "w",
        encoding="utf-8"
    ) as file:
        file.write(new_readme)

    print(
        f"README updated successfully: @{USERNAME}"
    )


if __name__ == "__main__":
    update_readme()
```
