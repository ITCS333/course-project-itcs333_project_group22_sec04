/*
  Requirement: Populate the single topic page and manage replies.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // replies for *this* topic only

// --- Element Selections ---
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const originalPost = document.querySelector('#original-post');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

// --- Functions ---

/**
 * Get topic id from URL (?id=...)
 */
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * Render the original post (OP) section.
 */
function renderOriginalPost(topic) {
  if (!topicSubject || !opMessage || !opFooter) return;

  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;

  // (Optional) زر حذف للـ OP
  if (originalPost) {
    // لو فيه div قديم للأكشن خله، لو ما فيه نضيف واحد بسيط
    let actions = originalPost.querySelector('.post-actions');
    if (!actions) {
      actions = document.createElement('div');
      actions.classList.add('post-actions');
      originalPost.appendChild(actions);
    } else {
      actions.innerHTML = '';
    }

    // Edit (شكلي بس)
    const editBtn = document.createElement('button');
    editBtn.type = 'button';
    editBtn.textContent = 'Edit';

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.textContent = 'Delete';
    deleteBtn.dataset.id = topic.id; // مجرد data-id لو حبيت تستخدمه بعدين

    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
  }
}

/**
 * Create one <article> for a reply.
 * reply = {id, author, date, text}
 */
function createReplyArticle(reply) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = reply.text;
  article.appendChild(p);

  const footer = document.createElement('footer');
  footer.textContent = `Reply by: ${reply.author} on ${reply.date}`;
  article.appendChild(footer);

  const actionsDiv = document.createElement('div');
  actionsDiv.classList.add('reply-actions');

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-reply-btn');
  deleteBtn.dataset.id = reply.id;

  actionsDiv.appendChild(deleteBtn);
  article.appendChild(actionsDiv);

  return article;
}

/**
 * Render all replies for this topic.
 */
function renderReplies() {
  if (!replyListContainer) return;

  replyListContainer.innerHTML = '';

  currentReplies.forEach((reply) => {
    const article = createReplyArticle(reply);
    replyListContainer.appendChild(article);
  });
}

/**
 * Handle add reply (form submit).
 */
function handleAddReply(event) {
  event.preventDefault();

  if (!newReplyText) return;
  const text = newReplyText.value.trim();

  if (!text) {
    // لو حاب تضيف alert
    // alert('Please write a reply before submitting.');
    return;
  }

  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student', // ثابت عشان التمرين
    date: new Date().toISOString().split('T')[0],
    text: text
  };

  currentReplies.push(newReply);
  renderReplies();
  newReplyText.value = '';
}

/**
 * Handle clicks in replies list (delete with delegation).
 */
function handleReplyListClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-reply-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    currentReplies = currentReplies.filter((reply) => reply.id !== id);
    renderReplies();
  }
}

/**
 * Initialize page:
 * - Get topic ID
 * - Fetch topics.json & replies.json
 * - Render OP + replies
 * - Attach listeners
 */
async function initializePage() {
  currentTopicId = getTopicIdFromURL();

  if (!currentTopicId) {
    if (topicSubject) {
      topicSubject.textContent = 'Topic not found.';
    }
    return;
  }

  try {
    const [topicsRes, repliesRes] = await Promise.all([
      fetch('topics.json'),
      fetch('replies.json')
    ]);

    const topicsData = await topicsRes.json();
    const repliesData = await repliesRes.json();

    // topics.json = array of topics
    const topic = Array.isArray(topicsData)
      ? topicsData.find((t) => t.id === currentTopicId)
      : null;

    // replies.json = object like { "topic_123": [ {id, author, date, text}, ... ] }
    if (repliesData && typeof repliesData === 'object') {
      currentReplies = Array.isArray(repliesData[currentTopicId])
        ? repliesData[currentTopicId]
        : [];
    } else {
      currentReplies = [];
    }

    if (!topic) {
      if (topicSubject) {
        topicSubject.textContent = 'Topic not found.';
      }
      return;
    }

    // Render OP + replies
    renderOriginalPost(topic);
    renderReplies();

    // Listeners
    if (replyForm) {
      replyForm.addEventListener('submit', handleAddReply);
    }
    if (replyListContainer) {
      replyListContainer.addEventListener('click', handleReplyListClick);
    }
  } catch (error) {
    console.error('Error initializing topic page:', error);
    if (topicSubject) {
      topicSubject.textContent = 'Error loading topic.';
    }
  }
}

// --- Initial Page Load ---
initializePage();
