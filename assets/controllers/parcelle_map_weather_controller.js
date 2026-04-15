import { Controller } from '@hotwired/stimulus';

// Leaflet is loaded via CDN in the Twig template (window.L)
export default class extends Controller {
  static targets = ['map', 'weather', 'weatherTitle'];
  static values = {
    weatherUrlTemplate: String,
    defaultLat: Number,
    defaultLon: Number,
    defaultZoom: Number,
  };

  connect() {
    // When navigating with Turbo, connect() can run before Leaflet finishes loading.
    // We defer initialization until both DOM + Leaflet are ready.
    this._ensureLeaflet();

    this._boot();

    // Re-bind when CRUD search updates the DOM
    document.addEventListener('crud:results-updated', this._onResultsUpdated);

    // Turbo navigation hooks (in case the controller stays alive across renders)
    document.addEventListener('turbo:load', this._boot);
    document.addEventListener('turbo:render', this._boot);
  }

  disconnect() {
    document.removeEventListener('crud:results-updated', this._onResultsUpdated);
    document.removeEventListener('turbo:load', this._boot);
    document.removeEventListener('turbo:render', this._boot);
  }

  _boot = () => {
    // This controller is meant for the parcelles index page.
    if (!this.hasMapTarget) {
      return;
    }

    // Leaflet may still be loading from CDN.
    if (!window.L) {
      window.setTimeout(this._boot, 50);
      return;
    }

    // Avoid initializing twice if Turbo triggers multiple renders.
    if (this._map) {
      // Still refresh markers/click bindings in case DOM changed.
      this._bindParcelleClicks(true);
      this._refreshMarkersFromDom();
      return;
    }

    this._initMap();
    this._bindParcelleClicks();
    this._autoSelectFirst();
  };

  _onResultsUpdated = () => {
    this._bindParcelleClicks(true);
    this._refreshMarkersFromDom();
  };

  _ensureLeaflet() {
    if (!window.L) {
      // Don't crash the page if CDN failed.
      // eslint-disable-next-line no-console
      console.warn('Leaflet not available (window.L). Map disabled.');
    }
  }

  _initMap() {
    if (!window.L || !this.hasMapTarget) return;

    const lat = Number.isFinite(this.defaultLatValue) ? this.defaultLatValue : 36.8065; // Tunis as fallback
    const lon = Number.isFinite(this.defaultLonValue) ? this.defaultLonValue : 10.1815;
    const zoom = Number.isFinite(this.defaultZoomValue) ? this.defaultZoomValue : 6;

    this._map = window.L.map(this.mapTarget).setView([lat, lon], zoom);

    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap',
    }).addTo(this._map);

    this._markersLayer = window.L.layerGroup().addTo(this._map);
    this._refreshMarkersFromDom();
  }

  _refreshMarkersFromDom() {
    if (!window.L || !this._markersLayer) return;

    this._markersLayer.clearLayers();

    const cards = document.querySelectorAll('.agri-parcelle-card[data-id]');
    cards.forEach((card) => {
      const lat = parseFloat(card.dataset.lat);
      const lon = parseFloat(card.dataset.lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

      const marker = window.L.marker([lat, lon]);
      marker.addTo(this._markersLayer);

      // Clicking marker behaves like clicking card
      marker.on('click', () => {
        this._selectParcelle(card);
      });

      // store for later highlight (optional)
      card._leafletMarker = marker;
    });
  }

  _bindParcelleClicks(force = false) {
    const cards = document.querySelectorAll('.agri-parcelle-card[data-id]');
    cards.forEach((card) => {
      if (!force && card.dataset.mapBound === '1') return;
      card.dataset.mapBound = '1';

      card.addEventListener('click', () => {
        this._selectParcelle(card);
      });

      // keyboard support
      card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this._selectParcelle(card);
        }
      });
    });
  }

  async _selectParcelle(card) {
    const id = card.dataset.id;
    if (!id) return;

    this._centerMapOnCard(card);
    await this._loadWeather(id, card);
  }

  _centerMapOnCard(card) {
    if (!window.L || !this._map) return;

    const lat = parseFloat(card.dataset.lat);
    const lon = parseFloat(card.dataset.lon);

    if (Number.isFinite(lat) && Number.isFinite(lon)) {
      this._map.setView([lat, lon], 14, { animate: true });

      if (card._leafletMarker) {
        card._leafletMarker.openPopup?.();
      }
    }
  }

  async _loadWeather(id, card) {
    if (!this.weatherUrlTemplateValue) return;

    const url = this.weatherUrlTemplateValue.replace('__ID__', String(id));

    if (this.hasWeatherTarget) {
      this.weatherTarget.innerHTML = '<div class="text-muted small">Chargement météo…</div>';
    }

    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      const parcelleName = data?.parcelle?.nom ?? ('Parcelle #' + id);
      const localisation = data?.parcelle?.localisation ?? '';

      if (this.hasWeatherTitleTarget) {
        this.weatherTitleTarget.textContent = localisation ? `${parcelleName} — ${localisation}` : parcelleName;
      }

      this._renderWeather(data?.weather);
    } catch (e) {
      if (this.hasWeatherTarget) {
        this.weatherTarget.innerHTML = '<div class="alert alert-warning py-2 small mb-0">Impossible de récupérer la météo.</div>';
      }
    }
  }

  _renderWeather(weather) {
    if (!this.hasWeatherTarget) return;

    if (!weather) {
      this.weatherTarget.innerHTML = '<div class="text-muted">Aucune météo disponible.</div>';
      return;
    }

    if (weather.ok === false) {
      const msg = weather.error || 'Erreur météo.';
      this.weatherTarget.innerHTML = `<div class="alert alert-warning py-2 small mb-0">${this._escapeHtml(msg)}</div>`;
      return;
    }

    const city = weather.city || '—';
    const icon = weather.icon
      ? `<img alt="icon" width="52" height="52" src="https://openweathermap.org/img/wn/${encodeURIComponent(weather.icon)}@2x.png">`
      : '';

    const temp = (typeof weather.temperature === 'number') ? weather.temperature.toFixed(1) : '—';
    const humidity = (typeof weather.humidity === 'number') ? weather.humidity : '—';
    const wind = (typeof weather.windSpeed === 'number') ? weather.windSpeed.toFixed(1) : '—';

    const desc = weather.description || '';
    const advice = weather.advice || '';

    this.weatherTarget.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">${this._escapeHtml(city)}</div>
        </div>
        <div class="text-end">${icon}</div>
      </div>
      <hr class="my-2">
      <div class="d-flex flex-wrap gap-2 mb-2">
        <span class="badge text-bg-success">${this._escapeHtml(temp)} °C</span>
        <span class="badge text-bg-primary">Humidité : ${this._escapeHtml(String(humidity))}%</span>
        <span class="badge text-bg-secondary">Vent : ${this._escapeHtml(wind)} m/s</span>
      </div>
      <div class="text-muted small mb-2">${this._escapeHtml(desc)}</div>
      <div class="small"><strong>Conseil :</strong> ${this._escapeHtml(advice)}</div>
    `;
  }

  _autoSelectFirst() {
    const first = document.querySelector('.agri-parcelle-card[data-id]');
    if (first) {
      this._selectParcelle(first);
    }
  }

  _escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }
}

