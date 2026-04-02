document.addEventListener('DOMContentLoaded', function () {
  const mapEls = document.querySelectorAll('.afso-video-map');
  if (!mapEls.length) return;

  const modal = createAfsoModal();

  fetch('/wp-json/afso/v1/videos')
    .then(res => res.json())
    .then(data => {
      mapEls.forEach(mapEl => {
        const countySlug = mapEl.dataset.countySlug || null;
        const postId = Number(mapEl.dataset.postId || 0);

        const map = L.map(mapEl).setView([51.5, -0.12], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const markers = L.markerClusterGroup({
            maxClusterRadius: 20,
            disableClusteringAtZoom: 7,
          showCoverageOnHover: false
        });

        data.forEach(video => {
          if (countySlug && video.county_slug !== countySlug) return;
          if (postId > 0 && Number(video.id) !== postId) return;

          const markerOptions = {};
          if (video.icon_url) {
            markerOptions.icon = L.icon({
              iconUrl: video.icon_url,
              iconSize: [32, 37],
              iconAnchor: [16, 37],
              popupAnchor: [0, -30]
            });
          }

          const marker = L.marker([video.lat, video.lng], markerOptions);

          // Marker click opens modal (replaces old inline popup iframe)
          marker.on('click', () => {
            openAfsoModal(modal, video, marker.getElement());
          });

          markers.addLayer(marker);
        });

        map.addLayer(markers);
        if (markers.getLayers().length) {
          map.fitBounds(markers.getBounds(), { padding: [24, 24], maxZoom: 13 });
        }
      });
    });

  function createAfsoModal() {
    const wrapper = document.createElement('div');
    wrapper.className = 'afso-modal';
    wrapper.setAttribute('hidden', 'hidden');
    wrapper.setAttribute('aria-hidden', 'true');

    wrapper.innerHTML = `
      <div class="afso-modal__backdrop" data-afso-close="1"></div>
      <div class="afso-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="afso-modal-title">
        <button type="button" class="afso-modal__close" aria-label="Close video" data-afso-close="1">×</button>
        <h3 id="afso-modal-title" class="afso-modal__title"></h3>
        <div class="afso-modal__video-wrap">
          <iframe class="afso-modal__iframe" width="560" height="315" src="" frameborder="0" allowfullscreen loading="lazy"></iframe>
        </div>
        <div class="afso-modal__content"></div>
        <p class="afso-modal__date"></p>
        <p class="afso-modal__link-wrap"><a class="afso-modal__link" href="#" target="_blank" rel="noopener">View Video</a></p>
      </div>
    `;

    document.body.appendChild(wrapper);

    wrapper.addEventListener('click', (e) => {
      const closeTrigger = e.target.closest('[data-afso-close="1"]');
      if (closeTrigger) {
        closeAfsoModal(wrapper);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !wrapper.hasAttribute('hidden')) {
        closeAfsoModal(wrapper);
      }
    });

    return wrapper;
  }

  function openAfsoModal(wrapper, video, returnFocusEl) {
    wrapper._returnFocusEl = returnFocusEl || null;

    const titleEl = wrapper.querySelector('.afso-modal__title');
    const iframeEl = wrapper.querySelector('.afso-modal__iframe');
    const contentEl = wrapper.querySelector('.afso-modal__content');
    const dateEl = wrapper.querySelector('.afso-modal__date');
    const linkEl = wrapper.querySelector('.afso-modal__link');

    titleEl.textContent = video.title || '';

    // Same core field as before: video_id -> embed URL
    const embedUrl = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(video.video_id || '')}`;
    iframeEl.src = embedUrl;

    // Same content field as before
    contentEl.textContent = video.content || '';

    // Same date field as before, but safe formatting
    let dateText = '';
    if (video.date) {
      const d = new Date(video.date);
      if (!Number.isNaN(d.getTime())) {
        dateText = `Date: ${d.toLocaleString()}`;
      } else {
        dateText = `Date: ${video.date}`;
      }
    }
    dateEl.textContent = dateText;
    dateEl.style.display = dateText ? '' : 'none';

    // Same link field as before
    linkEl.href = video.link || '#';

    wrapper.removeAttribute('hidden');
    wrapper.setAttribute('aria-hidden', 'false');
    document.body.classList.add('afso-modal-open');

    const closeBtn = wrapper.querySelector('.afso-modal__close');
    closeBtn.focus();
  }

  function closeAfsoModal(wrapper) {
    const iframeEl = wrapper.querySelector('.afso-modal__iframe');
    iframeEl.src = ''; // stop video/audio

    wrapper.setAttribute('hidden', 'hidden');
    wrapper.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('afso-modal-open');

    if (wrapper._returnFocusEl && typeof wrapper._returnFocusEl.focus === 'function') {
      wrapper._returnFocusEl.focus();
    }
  }
});