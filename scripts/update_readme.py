```python
import os
import requests
from datetime import datetime


# ============================================================
# SETTINGS
# ============================================================

USERNAME = os.environ["GITHUB_USERNAME"]

README_FILE = "README.md"

START_MARKER = "<!-- REPOSITORIES:START -->"
END_MARKER = "<!-- REPOSITORIES:END -->"

# Number of repositories to display
MAX_REPOSITORIES = 6


# ============================================================
# GITHUB API
# ============================================================

def get_repositories():

    url = f"https://api.github.com/users/{USERNAME}/repos"

    params = {
        "per_page": 100,
        "sort": "updated",
        "direction": "desc"
    }

    headers = {
        "Accept": "application/vnd.github+json"
    }

    response = requests.get(
        url,
        params=params,
        headers=headers,
        timeout=30
    )

    response.raise_for_status()

    repositories = response.json()

    # Remove forks and archived repositories
    repositories = [
        repo
        for repo in repositories
        if not repo["fork"]
        and not repo["archived"]
        and not repo["private"]
    ]

    return repositories


# ============================================================
# LANGUAGES
# ============================================================

def get_languages(repo):

    url = repo["languages_url"]

    headers = {
        "Accept": "application/vnd.github+json"
    }

    response = requests.get(
        url,
        headers=headers,
        timeout=30
    )

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

        # Only show languages >= 5%
        if percentage >= 5:

            result.append({
                "name": language,
                "percentage": round(percentage)
            })

    return result[:4]


# ============================================================
# REPOSITORY CARD
# ============================================================

def create_repository_card(repo):

    name = repo["name"]

    description = repo["description"] or "No description available."

    # Limit description length
    if len(description) > 100:
        description = description[:97] + "..."

    languages = get_languages(repo)

    # --------------------------------------------------------
    # Languages
    # --------------------------------------------------------

    language_html = ""

    if languages:

        language_items = []

        for language in languages:

            language_items.append(
                f'<code>{language["name"]}</code>'
            )

        language_html = " · ".join(language_items)

    else:

        language_html = "<code>Other</code>"

    # --------------------------------------------------------
    # Repository information
    # --------------------------------------------------------

    stars = repo["stargazers_count"]

    forks = repo["forks_count"]

    updated = datetime.fromisoformat(
        repo["updated_at"].replace("Z", "+00:00")
    )

    updated_text = updated.strftime("%Y-%m-%d")

    url = repo["html_url"]

    # --------------------------------------------------------
    # Card
    # --------------------------------------------------------

    card = f"""
<td width="50%" valign="top">

<h3>
<a href="{url}">🚀 {name}</a>
</h3>

<p>
{description}
</p>

<p>
{language_html}
</p>

<p>
⭐ {stars} &nbsp; · &nbsp; 🍴 {forks}
<br>
<sub>Updated {updated_text}</sub>
</p>

</td>
"""

    return card


# ============================================================
# GENERATE REPOSITORIES
# ============================================================

def generate_repositories():

    repositories = get_repositories()

    # Limit number of repositories
    repositories = repositories[:MAX_REPOSITORIES]

    rows = []

    # Create 2-column layout
    for i in range(0, len(repositories), 2):

        first_card = create_repository_card(
            repositories[i]
        )

        if i + 1 < len(repositories):

            second_card = create_repository_card(
                repositories[i + 1]
            )

        else:

            second_card = '<td width="50%"></td>'

        row = f"""
<tr>

{first_card}

{second_card}

</tr>
"""

        rows.append(row)

    repositories_html = "".join(rows)

    return f"""
<table>
<tbody>

{repositories_html}

</tbody>
</table>
"""


# ============================================================
# UPDATE README
# ============================================================

def update_readme():

    # Read README
    with open(
        README_FILE,
        "r",
        encoding="utf-8"
    ) as file:

        readme = file.read()

    # Find markers
    start = readme.find(START_MARKER)

    end = readme.find(END_MARKER)

    if start == -1 or end == -1:

        raise RuntimeError(
            "Repository markers were not found in README.md"
        )

    # Generate repository section
    repositories = generate_repositories()

    # Position after START marker
    content_start = start + len(START_MARKER)

    # Create new README
    new_readme = (
        readme[:content_start]
        + "\n"
        + repositories
        + "\n"
        + readme[end:]
    )

    # Save README
    with open(
        README_FILE,
        "w",
        encoding="utf-8"
    ) as file:

        file.write(new_readme)

    print(
        f"README updated successfully for @{USERNAME}"
    )


# ============================================================
# MAIN
# ============================================================

if __name__ == "__main__":

    update_readme()
```
