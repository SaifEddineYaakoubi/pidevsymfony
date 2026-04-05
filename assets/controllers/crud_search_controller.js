import { Controller } from '@hotwired/stimulus';

/**
 * AJAX live search for CRUD lists.
 *
 * Usage:
 *  - data-controller="crud-search"
 *  - data-crud-search-url-value="/agriculteur/parcelles"
 *  - input: data-crud-search-target="input"
 *  - results container: data-crud-search-target="results"
 *  - optional: data-crud-search-target="counter"
 *
 * The endpoint must accept `?q=...` and if header X-Requested-With=XMLHttpRequest
 * it should render a partial HTML with only the table body (or results block).
 */
export default class extends Controller {
  static targets = ['input', 'counter', 'sort', 'dir'];
  static values = {
    url: String,
    resultsSelector: { type: String, default: '[data-crud-search-target="results"]' },
    delay: { type: Number, default: 250 }
  };

  connect() {
    this._timer = null;
  }

  /**
   * Because we replace the results HTML, any <select> inside results would be recreated.
   * Stimulus targets are not automatically re-scanned when we mutate innerHTML.
   * We therefore re-scan for sort/dir controls after each update.
   */
  _refreshSortTargets() {
    const sort = this.element.querySelector('[data-crud-search-target="sort"]');
    const dir = this.element.querySelector('[data-crud-search-target="dir"]');
    this.sortTarget = sort || this.sortTarget;
    this.dirTarget = dir || this.dirTarget;
  }

  search() {
    window.clearTimeout(this._timer);
    this._timer = window.setTimeout(() => this._doSearch(), this.delayValue);
  }

  async _doSearch() {
    const resultsEl = this.element.querySelector(this.resultsSelectorValue);
    if (!resultsEl) {
      return;
    }
    const q = this.inputTarget.value ?? '';
    const url = new URL(this.urlValue, window.location.origin);
    url.searchParams.set('q', q);

    if (this.hasSortTarget) {
      url.searchParams.set('sort', this.sortTarget.value);
    }
    if (this.hasDirTarget) {
      url.searchParams.set('dir', this.dirTarget.value);
    }

    try {
      resultsEl.classList.add('is-loading');
      const res = await fetch(url.toString(), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!res.ok) {
        return;
      }

      const html = await res.text();

      // Keep the loader overlay (first element with class .agri-crud-loader)
      const loader = resultsEl.querySelector('.agri-crud-loader');
      resultsEl.innerHTML = html;
      if (loader) {
        resultsEl.prepend(loader);
      }

      this._refreshSortTargets();

      // Update URL without reload
      const newUrl = new URL(window.location.href);
      if (q.trim() === '') {
        newUrl.searchParams.delete('q');
      } else {
        newUrl.searchParams.set('q', q);
      }

      if (this.hasSortTarget) {
        newUrl.searchParams.set('sort', this.sortTarget.value);
      }
      if (this.hasDirTarget) {
        newUrl.searchParams.set('dir', this.dirTarget.value);
      }
      window.history.replaceState({}, '', newUrl);

      // Re-init selection buttons by triggering a custom event
      this.element.dispatchEvent(new CustomEvent('crud:results-updated', { bubbles: true }));
    } catch (e) {
      // ignore network errors
    } finally {
      resultsEl.classList.remove('is-loading');
    }
  }

  sortChanged() {
    // immediate refresh
    this._doSearch();
  }
}

