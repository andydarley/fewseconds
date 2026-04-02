document.addEventListener('DOMContentLoaded', function () {
  const mapEls = document.querySelectorAll('.afso-video-map');
  if (!mapEls.length) return;

  fetch('/wp-json/afso/v1/videos')
    .then(res => res.json())
    .then(data => {
      mapEls.forEach(mapEl => {
        const countySlug = mapEl.dataset.countySlug || null;
        const postId = Number(mapEl.dataset.postId || 0);
        const map = L.map(mapEl).setView([51.5, -0.12], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const markers = L.markerClusterGroup();

        data.forEach(video => {
          if (countySlug && video.county_slug !== countySlug) return;
          if (postId > 0 && Number(video.id) !== postId) return;

          let dateHtml = '';
          if (video.date) {
            const parsedDate = new Date(video.date);
            if (!Number.isNaN(parsedDate.getTime())) {
              dateHtml = `<p><strong>Date:</strong> ${parsedDate.toLocaleString()}</p>`;
            }
          }

          const popup = `<h3>${video.title}</h3>
          <iframe width='280' height='158' src='https://www.youtube-nocookie.com/embed/${video.video_id}' frameborder='0' allowfullscreen></iframe>
          <p>${video.content || ''}</p>
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
        if (markers.getLayers().length) {
          map.fitBounds(markers.getBounds(), { padding: [24, 24], maxZoom: 13 });
        }
      });
    });
});