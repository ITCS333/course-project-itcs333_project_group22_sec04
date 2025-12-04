/*
  Requirement: Make the "Discussion Board" page interactive.

  This script:
  - يحمل المواضيع من topics.json
  - يعرضها في الصفحة
  - يسمح لك تضيف وتحذف مواضيع (in-memory بس)
*/

// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const newTopicForm = document.querySelector('#new-topic-form');
const topicListContainer = document.querySelector('#topic-list-container');

// --- Functions ---

/**
 * Create one <article> element for a topic.
 * topic = { id, subject, author, date, message? }
 */
function createTopicArticle(topic) {
  const article = document.createElement('article');

  // عنوان الموضوع
  const h3 = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h3.appendChild(link);
  article.appendChild(h3);

  // Footer للمعلومات
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  article.appendChild(footer);

  // Actions (Edit + Delete)
  const actionsDiv = document.createElement('div');
  actionsDiv.classList.add('topic-actions');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  // مافيه منطق تعديل حقيقي حالياً – بس زر شكلي عشان التصميم

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = topic.id;

  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);

  article.appendChild(actionsDiv);

  return article;
}

/**
 * Render all topics into the container.
 */
function renderTopics() {
  if (!topicListContainer) return;

  // فضي المحتوى القديم
  topicListContainer.innerHTML = '';

  // أضف كل موضوع
  topics.forEach((topic) => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * Handle new topic creation (form submit).
 */
function handleCreateTopic(event) {
  event.preventDefault();

  const subjectInput = document.querySelector('#topic-subject');
  const messageInput = document.querySelector('#topic-message');

  const subject = subjectInput ? subjectInput.value.trim() : '';
  const message = messageInput ? messageInput.value.trim() : '';

  if (!subject || !message) {
    // ممكن تضيف alert بسيطة لو حاب، بس الفاليديشن في HTML already
    alert('Please fill in both subject and message.');
    return;
  }

  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subject,
    message: message,
    author: 'Student', // ثابت عشان التمرين
    date: new Date().toISOString().split('T')[0] // YYYY-MM-DD
  };

  // Add to global array
  topics.push(newTopic);

  // Re-render list
  renderTopics();

  // Reset form
  if (newTopicForm) {
    newTopicForm.reset();
  }
}

/**
 * Handle clicks inside the topics container (delete via delegation).
 */
function handleTopicListClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-btn')) {
    const id = target.dataset.id;
    if (!id) return;

    // احذف الموضوع من الذاكرة
    topics = topics.filter((topic) => topic.id !== id);

    // أعد الرسم
    renderTopics();
  }
}

/**
 * Load topics from topics.json and initialize listeners.
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('topics.json');
    if (!response.ok) {
      console.error('Failed to load topics.json:', response.status);
      topics = [];
    } else {
      const data = await response.json();
      // نتوقع مصفوفة مواضيع بنفس الهيكل {id, subject, author, date, message?}
      topics = Array.isArray(data) ? data : [];
    }
  } catch (error) {
    console.error('Error fetching topics.json:', error);
    topics = [];
  }

  // أول رسم
  renderTopics();

  // Event listeners
  if (newTopicForm) {
    newTopicForm.addEventListener('submit', handleCreateTopic);
  }

  if (topicListContainer) {
    topicListContainer.addEventListener('click', handleTopicListClick);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
