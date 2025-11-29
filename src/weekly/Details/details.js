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
  const params = new URLSearchParams(window.location.search);
  return params.get("id") || "week_1"; // default
}


/**
 * Render one week's details
 */
function renderWeekDetails(week) {
  weekTitle.textContent = week.title;

  // Preserve <strong> and <time> structure
  const timeEl = weekStartDate.querySelector("time");
  timeEl.setAttribute("datetime", week.startDate);
  timeEl.textContent = week.startDate;

  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";
  week.links.forEach((url) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = url;
    a.textContent = url;
    a.rel = "noopener noreferrer";
    a.target = "_blank";
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

  try {
    const [weeksRes, commentsRes] = await Promise.all([
      fetch("weeks.json"),
      fetch("comments.json"),
    ]);

    if (!weeksRes.ok || !commentsRes.ok) {
      throw new Error("Failed to load data files");
    }

    const weeksData = await weeksRes.json();
    const commentsData = await commentsRes.json();

    const week = weeksData.find((w) => w.id === currentWeekId);
    currentComments = Array.isArray(commentsData[currentWeekId]) ? commentsData[currentWeekId] : [];

    if (!week) {
      weekTitle.textContent = "Week not found.";
      return;
    }

    renderWeekDetails(week);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);
  } catch (error) {
    weekTitle.textContent = "Error loading week data.";
    console.error("Initialization error:", error);
  }
}


initializePage();