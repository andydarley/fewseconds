document.addEventListener('DOMContentLoaded', function () {
  const mapEl = document.getElementById('video-map');
  if (!mapEl) return;
  const county = mapEl.dataset.county || null;
  const map = L.map(mapEl).setView([51.5, -0.12], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  const markers = L.markerClusterGroup();
  fetch('/wp-json/yvm/v1/videos').then(res => res.json()).then(data => {
    data.forEach(video => {
      if (!county || video.county === county) {
        const marker = L.marker([video.lat, video.lng]);
        const popup = `<h3>${video.title}</h3><iframe width='280' height='158' src='https://www.youtube.com/embed/${video.video_id}' frameborder='0' allowfullscreen></iframe><p>${video.content}</p><p><strong>Date:</strong> ${new Date(video.date).toLocaleString()}</p><p><a href='${video.link}'>More Info</a></p>`;
        marker.bindPopup(popup);
        markers.addLayer(marker);
      }
    });
    map.addLayer(markers);
  });
});