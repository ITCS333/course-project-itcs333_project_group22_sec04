/*
  Populate the "Course Resources" list page.
*/

// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

/**
 * Create one <article> for a resource.
 * resource = { id, title, description, link? }
 */
function createResourceArticle(resource) {
  const article = document.createElement('article');

  // عنوان الريسورس
  const heading = document.createElement('h2');
  heading.textContent = resource.title || 'Untitled Resource';
  article.appendChild(heading);

  // الوصف
  const desc = document.createElement('p');
  desc.textContent = resource.description || '';
  article.appendChild(desc);

  // الرابط لصفحة التفاصيل + النقاش
  const link = document.createElement('a');
  link.href = `details.html?id=${resource.id}`;
  link.textContent = 'View Resource & Discussion';
  article.appendChild(link);

  return article;
}

/**
 * Load resources from resources.json and render them.
 */
async function loadResources() {
  if (!listSection) return;

  try {
    const response = await fetch('resources.json');
    if (!response.ok) {
      console.error('Failed to load resources.json:', response.status);
      listSection.innerHTML = '<p>Unable to load resources.</p>';
      return;
    }

    const data = await response.json();
    const resources = Array.isArray(data) ? data : [];

    // تفريغ المحتوى القديم
    listSection.innerHTML = '';

    // إنشاء articles لكل ريسورس
    resources.forEach((resource) => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });

    // لو ما فيه أي ريسورس
    if (resources.length === 0) {
      listSection.innerHTML = '<p>No resources available yet.</p>';
    }
  } catch (error) {
    console.error('Error loading resources:', error);
    listSection.innerHTML = '<p>Error loading resources.</p>';
  }
}

// --- Initial Page Load ---
loadResources();
