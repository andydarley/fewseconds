document.addEventListener('DOMContentLoaded', function () {
  const mapEl = document.getElementById('video-map');
  if (!mapEl) return;
  const map = L.map(mapEl).setView([51.5, -0.12], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  const markers = L.markerClusterGroup();
  fetch('/wp-json/afso/v1/videos')
    .then(res => res.json())
    .then(data => {
      data.forEach(video => {
        const popup = `<h3>${video.title}</h3>
        <iframe width='280' height='158' src='https://www.youtube-nocookie.com/embed/${video.video_id}' frameborder='0' allowfullscreen></iframe>
        <p>${video.content}</p>
        <p><strong>Date:</strong> ${new Date(video.date).toLocaleString()}</p>
        <p><a href='${video.link}'>View Video</a></p>`;
        const marker = L.marker([video.lat, video.lng]);
        marker.bindPopup(popup);
        markers.addLayer(marker);
      });
      map.addLayer(markers);
    });
});