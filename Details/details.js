// --- Global Data Store ---
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

/**
 * Get ?id=... from the URL
 */
function getWeekIdFromURL() {
  const queryString = window.location.search;
  const params = new URLSearchParams(queryString);
  return params.get("id");
}

/**
 * Render one week's details
 */
function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = "Starts on: " + week.startDate;
  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";
  week.links.forEach((linkUrl) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = linkUrl;
    a.textContent = linkUrl;
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

/**
 * Create an <article> element for one comment
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.className = "comment";

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  const today = new Date().toISOString().split("T")[0];
  footer.innerHTML = `Posted by: <strong>${comment.author}</strong> on <time datetime="${today}">${today}</time>`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

/**
 * Render all comments in currentComments
 */
function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

/**
 * Handle "Post Comment"
 */
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newCommentText.value.trim();
  if (commentText === "") return;

  const newComment = {
    author: "Student",
    text: commentText,
  };

  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

/**
 * Initialize page
 */
async function initializePage() {
  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  try {
    // NOTE: filenames must match your actual files: weeks.json & comments.json
    const [weeksRes, commentsRes] = await Promise.all([
      fetch("weeks.json"),
      fetch("comments.json"),
    ]);

    const weeksData = await weeksRes.json();
    const commentsData = await commentsRes.json();

    const week = weeksData.find((w) => w.id === currentWeekId);
    currentComments = commentsData[currentWeekId] || [];

    if (week) {
      renderWeekDetails(week);
      renderComments();
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      weekTitle.textContent = "Week not found.";
    }
  } catch (error) {
    weekTitle.textContent = "Error loading week data.";
    console.error("Initialization error:", error);
  }
}

initializePage();