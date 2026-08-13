(function () {
  'use strict';

  const STR_PLACEHOLDER = 'Search layouts, media, displays…';
  const STR_NO_RESULTS = 'No results found';
  const STR_FILTER_TIP = 'Tip: use the checkboxes to filter which item types are returned.';

  const isMac =
    navigator.platform &&
    /Mac/.test(navigator.platform);

  const HOTKEY = (e) =>
    (isMac ? e.metaKey : e.ctrlKey) &&
    e.key &&
    e.key.toLowerCase() === 'k';

  let overlay = null;
  let box = null;
  let input = null;
  let list = null;
  let items = [];
  let idx = 0;
  let aborter = null;
  let openingItem = false;

  const createElement = (tag, className) => {
    const element = document.createElement(tag);

    if (className) {
      element.className = className;
    }

    return element;
  };

  const debounce = (fn, ms) => {
    let timeout = null;

    return (...args) => {
      if (timeout) {
        clearTimeout(timeout);
      }

      timeout = setTimeout(
        () => fn(...args),
        ms
      );
    };
  };

  function openQuickSwitcher() {
    if (box) {
      return;
    }

    overlay = createElement(
      'div',
      'QuickSwitcher-overlay'
    );

    box = createElement(
      'div',
      'QuickSwitcher'
    );

    input = createElement('input');
    input.type = 'search';
    input.placeholder = STR_PLACEHOLDER;
    input.setAttribute(
      'aria-label',
      'Quick switcher search'
    );

    const top = createElement(
      'div',
      'QuickSwitcher-top'
    );

    top.appendChild(input);

    const filters = createElement(
      'div',
      'QuickSwitcher-filters'
    );

    const types = [
      ['all', 'All'],
      ['layout', 'Layouts'],
      ['campaign', 'Campaigns'],
      ['playlist', 'Playlists'],
      ['display', 'Displays'],
      ['media', 'Media'],
      ['navigation', 'Navigation']
    ];

    types.forEach(([value, labelText]) => {
      const label = createElement(
        'label',
        'QuickSwitcher-filter-label'
      );

      const checkbox = createElement('input');

      checkbox.type = 'checkbox';
      checkbox.className =
        'QuickSwitcher-type-checkbox';
      checkbox.value = value;
      checkbox.checked = true;

      const text = createElement(
        'span',
        'QuickSwitcher-filter-text'
      );

      text.textContent = labelText;

      label.appendChild(checkbox);
      label.appendChild(text);

      filters.appendChild(label);
    });

    const note = createElement(
      'div',
      'QuickSwitcher-folders-note'
    );

    note.textContent = STR_FILTER_TIP;

    list = createElement(
      'div',
      'QuickSwitcher-list'
    );

    list.setAttribute(
      'role',
      'listbox'
    );

    box.appendChild(top);
    box.appendChild(filters);
    box.appendChild(note);
    box.appendChild(list);

    document.body.appendChild(overlay);
    document.body.appendChild(box);

    overlay.addEventListener(
      'click',
      closeQuickSwitcher
    );

    input.addEventListener(
      'input',
      debounce(fetchResults, 120)
    );

    filters.addEventListener(
      'change',
      handleFilterChange
    );

    document.addEventListener(
      'keydown',
      navHandler,
      true
    );

    setTimeout(() => {
      input.focus();
    }, 0);

    fetchResults();
  }

  function handleFilterChange(e) {
    const checkboxes = Array.from(
      box.querySelectorAll(
        '.QuickSwitcher-type-checkbox'
      )
    );

    const master = checkboxes.find(
      (checkbox) => checkbox.value === 'all'
    );

    const others = checkboxes.filter(
      (checkbox) => checkbox.value !== 'all'
    );

    const target = e.target;

    if (
      !target ||
      target.type !== 'checkbox'
    ) {
      return;
    }

    if (target.value === 'all') {
      others.forEach((checkbox) => {
        checkbox.checked = target.checked;
      });
    } else if (target.checked) {
      others.forEach((checkbox) => {
        checkbox.checked = checkbox === target;
      });

      master.checked = false;
    } else {
      master.checked = others.every(
        (checkbox) => checkbox.checked
      );
    }

    fetchResults();
  }

  function closeQuickSwitcher() {
    if (aborter) {
      try {
        aborter.abort();
      } catch (e) {
      }
    }

    document.removeEventListener(
      'keydown',
      navHandler,
      true
    );

    if (
      overlay &&
      overlay.parentNode
    ) {
      overlay.parentNode.removeChild(overlay);
    }

    if (
      box &&
      box.parentNode
    ) {
      box.parentNode.removeChild(box);
    }

    overlay = null;
    box = null;
    input = null;
    list = null;
    items = [];
    idx = 0;
    openingItem = false;
  }

  function navHandler(e) {
    if (!box) {
      return;
    }

    if (e.key === 'Escape') {
      e.preventDefault();
      closeQuickSwitcher();
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      move(1);
      return;
    }

    if (e.key === 'ArrowUp') {
      e.preventDefault();
      move(-1);
      return;
    }

    if (e.key === 'Enter') {
      e.preventDefault();

      if (items[idx]) {
        openItem(items[idx]);
      }
    }
  }

  function move(delta) {
    if (!list || items.length === 0) {
      return;
    }

    idx = Math.max(
      0,
      Math.min(
        items.length - 1,
        idx + delta
      )
    );

    updateSelection();

    const row = list.children[idx];

    if (
      row &&
      typeof row.scrollIntoView === 'function'
    ) {
      row.scrollIntoView({
        block: 'nearest'
      });
    }
  }

  function updateSelection() {
    if (!list) {
      return;
    }

    Array.from(list.children).forEach(
      (row, rowIndex) => {
        const selected =
          rowIndex === idx;

        row.setAttribute(
          'aria-selected',
          selected ? 'true' : 'false'
        );

        row.classList.toggle(
          'QuickSwitcher-active',
          selected
        );
      }
    );
  }

  function render() {
    if (!list) {
      return;
    }

    list.innerHTML = '';

    const query =
      input && input.value
        ? input.value.trim()
        : '';

    if (!items.length) {
      if (query !== '') {
        const noResults = createElement(
          'div',
          'QuickSwitcher-no-results'
        );

        noResults.setAttribute(
          'role',
          'status'
        );

        noResults.textContent =
          STR_NO_RESULTS;

        list.appendChild(noResults);
      }

      return;
    }

    items.forEach((item, itemIndex) => {
      const row = createElement(
        'div',
        'QuickSwitcher-item'
      );

      row.setAttribute(
        'role',
        'option'
      );

      row.setAttribute(
        'aria-selected',
        itemIndex === idx
          ? 'true'
          : 'false'
      );

      const type = createElement(
        'span',
        'QuickSwitcher-type'
      );

      type.textContent = item.type || '';

      const label = createElement(
        'span',
        'QuickSwitcher-label'
      );

      label.textContent = item.label || '';

      row.appendChild(type);
      row.appendChild(label);

      if (item.hint) {
        const hint = createElement(
          'span',
          'QuickSwitcher-hint'
        );

        hint.textContent = item.hint;

        row.appendChild(hint);
      }

      row.addEventListener(
        'mouseenter',
        () => {
          idx = itemIndex;
          updateSelection();
        }
      );

      row.addEventListener(
        'click',
        (e) => {
          e.preventDefault();
          openItem(item);
        }
      );

      list.appendChild(row);
    });

    updateSelection();
  }

  function getXsrfToken() {
    const meta =
      document.querySelector(
        'meta[name="csrf-token"], meta[name="xsrf-token"]'
      );

    if (meta && meta.content) {
      return meta.content;
    }

    const match = document.cookie.match(
      /(?:^|;\s*)XSRF-TOKEN=([^;]*)/
    );

    if (match) {
      try {
        return decodeURIComponent(match[1]);
      } catch (e) {
        return match[1];
      }
    }

    return '';
  }

  async function getPreference(option) {
    const response = await fetch(
      '/json/user/pref?preference=' +
      encodeURIComponent(option),
      {
        method: 'GET',
        credentials: 'include',
        headers: {
          accept:
            'application/json, text/plain, */*'
        }
      }
    );

    if (!response.ok) {
      throw new Error(
        'Could not read preference ' +
        option +
        ': HTTP ' +
        response.status
      );
    }

    const data = await response.json();

    if (
      data &&
      typeof data.value === 'string'
    ) {
      return JSON.parse(data.value);
    }

    if (
      data &&
      data.value &&
      typeof data.value === 'object'
    ) {
      return data.value;
    }

    return {};
  }


  async function savePreference(
    option,
    preference
  ) {
    const form = new URLSearchParams();

    form.set(
      'preference[0][option]',
      option
    );

    form.set(
      'preference[0][value]',
      JSON.stringify(preference)
    );

    const headers = {
      accept:
        'application/json, text/plain, */*',
      'content-type':
        'application/x-www-form-urlencoded'
    };

    const xsrfToken = getXsrfToken();

    if (xsrfToken) {
      headers['x-xsrf-token'] =
        xsrfToken;
    }

    const response = await fetch(
      '/json/user/pref',
      {
        method: 'POST',
        credentials: 'include',
        headers: headers,
        body: form.toString()
      }
    );

    if (!response.ok) {
      const body =
        await response.text();

      throw new Error(
        'Could not save preference ' +
        option +
        ': HTTP ' +
        response.status +
        ' ' +
        body
      );
    }
  }

  async function applyXiboFilter(item) {
    const option = item.preference;
    const filterField = item.filterField;
    const filterValue = item.filterValue;

    if (
      !option ||
      !filterField
    ) {
      return;
    }

    const preference =
      await getPreference(option);

    if (
      !preference ||
      typeof preference !== 'object'
    ) {
      throw new Error(
        'Invalid Xibo preference response'
      );
    }

    if (
      !preference.filterInputs ||
      typeof preference.filterInputs !== 'object'
    ) {
      preference.filterInputs = {};
    }

    preference.filterInputs[filterField] =
      filterValue;

    preference.filterInputs.logicalOperatorName =
      preference.filterInputs.logicalOperatorName ||
      'OR';

    preference.filterInputs.logicalOperator =
      preference.filterInputs.logicalOperator ||
      'OR';

    preference.filterInputs.exactTags = false;

    preference.folderId = null;

    preference.time = Date.now();

    console.log(
      'QuickSwitcher saving Xibo filter',
      {
        option: option,
        filterField: filterField,
        filterValue: filterValue,
        preference: preference
      }
    );

    await savePreference(
      option,
      preference
    );

    const verified =
      await getPreference(option);

    if (
      !verified.filterInputs ||
      String(
        verified.filterInputs[filterField] || ''
      ) !== String(filterValue)
    ) {
      throw new Error(
        'Xibo did not persist the filter'
      );
    }

    if (verified.folderId !== null) {
      throw new Error(
        'Xibo did not persist All Items'
      );
    }

    return verified;
  }

  async function openItem(item) {
    if (
      !item ||
      !item.url ||
      openingItem
    ) {
      return;
    }

    openingItem = true;

    try {
      if (
        item.action ===
        'apply-xibo-filter'
      ) {
        await applyXiboFilter(item);
      }

      window.location.href = item.url;
    } catch (error) {
      openingItem = false;

      console.error(
        'QuickSwitcher filter error:',
        error
      );

      alert(
        'The Xibo filter could not be applied: ' +
        error.message
      );
    }
  }

  async function fetchResults() {
    const query =
      input && input.value
        ? input.value.trim()
        : '';

    if (aborter) {
      try {
        aborter.abort();
      } catch (e) {
      }
    }

    aborter = new AbortController();

    try {
      const checked = box
        ? Array.from(
          box.querySelectorAll(
            '.QuickSwitcher-type-checkbox'
          )
        )
          .filter(
            (checkbox) => checkbox.checked
          )
          .map(
            (checkbox) => checkbox.value
          )
        : ['all'];

      const typeParam =
        !checked.includes('all')
          ? checked.join(',')
          : 'all';

      const response = await fetch(
        '/QuickSwitcher/search?q=' +
        encodeURIComponent(query) +
        '&type=' +
        encodeURIComponent(typeParam),
        {
          signal: aborter.signal,
          credentials: 'same-origin'
        }
      );

      if (!response.ok) {
        throw new Error('Network error');
      }

      const json =
        await response.json();

      items = json.results || [];
      idx = 0;

      render();
    } catch (error) {
      if (
        error &&
        error.name === 'AbortError'
      ) {
        return;
      }

      console.error(
        'QuickSwitcher search error:',
        error
      );

      items = [];
      idx = 0;
      render();
    }
  }

  window.addEventListener(
    'keydown',
    (e) => {
      const tag =
        (
          e.target &&
          e.target.tagName
            ? e.target.tagName
            : ''
        ).toLowerCase();

      if (
        (
          tag === 'input' ||
          tag === 'textarea' ||
          (
            e.target &&
            e.target.isContentEditable
          )
        ) &&
        !(
          e.metaKey ||
          e.ctrlKey
        )
      ) {
        return;
      }

      if (HOTKEY(e)) {
        e.preventDefault();
        openQuickSwitcher();
      }
    }
  );

  window.__xiboQuickSwitcher = {
    open: openQuickSwitcher,
    close: closeQuickSwitcher
  };
})();