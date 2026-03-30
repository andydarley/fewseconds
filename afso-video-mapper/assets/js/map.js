document.addEventListener('DOMContentLoaded', function () {
  const mapEl = document.getElementById('video-map');
  if (!mapEl) return;

  const countySlug = mapEl.dataset.countySlug || null;
  const map = L.map(mapEl).setView([51.5, -0.12], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  const markers = L.markerClusterGroup();

  fetch('/wp-json/afso/v1/videos')
    .then(res => res.json())
    .then(data => {
      data.forEach(video => {
        if (countySlug && video.county_slug !== countySlug) return;

        let dateHtml = '';
        if (video.date) {
          const d = new Date(video.date);
          if (!Number.isNaN(d.getTime())) {
            dateHtml = `<p><strong>Date:</strong> ${d.toLocaleString()}</p>`;
          }
        }

        const popup = `<h3>${video.title}</h3>
        <iframe width='280' height='158' src='https://www.youtube-nocookie.com/embed/${video.video_id}' frameborder='0' allowfullscreen></iframe>
        <p>${video.content}</p>
        ${dateHtml}
        <p><a href='${video.link}'>View Video</a></p>`;

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
        marker.bindPopup(popup);
        markers.addLayer(marker);
      });

      map.addLayer(markers);

      if (markers.getLayers().length > 0) {
        map.fitBounds(markers.getBounds(), { padding: [24, 24], maxZoom: 13 });
      }
    });
});