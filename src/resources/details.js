/*
  Populate the resource detail page and discussion forum.
*/

// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

// --- Functions ---

/**
 * Get resource id from URL (?id=...)
 */
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * Render main resource info.
 */
function renderResourceDetails(resource) {
  if (!resourceTitle || !resourceDescription || !resourceLink) return;

  resourceTitle.textContent = resource.title || 'Untitled Resource';
  resourceDescription.textContent = resource.description || '';
  if (resource.link) {
    resourceLink.href = resource.link;
  }
}

/**
 * Create one <article> for a comment.
 * comment = { author, text }
 */
function createCommentArticle(comment) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = comment.text || '';
  article.appendChild(p);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author || 'Student'}`;
  article.appendChild(footer);

  return article;
}

/**
 * Render all comments of this resource.
 */
function renderComments() {
  if (!commentList) return;

  commentList.innerHTML = '';

  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

/**
 * Handle add comment (form submit).
 */
function handleAddComment(event) {
  event.preventDefault();

  if (!newComment) return;
  const text = newComment.value.trim();

  if (!text) {
    // ممكن تحط alert لو حاب:
    // alert('Please write a comment before posting.');
    return;
  }

  const newCommentObj = {
    author: 'Student',
    text: text
  };

  currentComments.push(newCommentObj);
  renderComments();
  newComment.value = '';
}

/**
 * Initialize page:
 * - get resource id
 * - fetch resources + comments
 * - render details + comments
 * - add listeners
 */
async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    if (resourceTitle) {
      resourceTitle.textContent = 'Resource not found.';
    }
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch('resources.json'),
      fetch('resource-comments.json')
    ]);

    const resourcesData = await resourcesRes.json();
    const commentsData = await commentsRes.json();

    // resources.json: array of resources
    const resource = Array.isArray(resourcesData)
      ? resourcesData.find((r) => r.id === currentResourceId)
      : null;

    // resource-comments.json: object: { "res_123": [ {author, text}, ... ] }
    if (commentsData && typeof commentsData === 'object') {
      currentComments = Array.isArray(commentsData[currentResourceId])
        ? commentsData[currentResourceId]
        : [];
    } else {
      currentComments = [];
    }

    if (!resource) {
      if (resourceTitle) {
        resourceTitle.textContent = 'Resource not found.';
      }
      return;
    }

    // Render details + comments
    renderResourceDetails(resource);
    renderComments();

    // Listener for adding new comments
    if (commentForm) {
      commentForm.addEventListener('submit', handleAddComment);
    }
  } catch (error) {
    console.error('Error initializing resource details page:', error);
    if (resourceTitle) {
      resourceTitle.textContent = 'Error loading resource.';
    }
  }
}

// --- Initial Page Load ---
initializePage();
