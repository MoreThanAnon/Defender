@extends('layouts.app')

@section('content')
<div class="container"> <!-- Add container to limit width -->
    @include('partials.navbar') <!-- Include the navbar -->
</div>
<div class="container mt-5">
    <h1>Properties</h1>
    <!-- ...existing code for displaying properties... -->
    <div id="map" style="height: 400px; width: 100%;"></div>
</div>
<script>
    function initMap() {
        const map = new google.maps.Map(document.getElementById("map"), {
            center: { lat: -34.397, lng: 150.644 },
            zoom: 8,
        });

        // Example of using AdvancedMarkerElement instead of Marker
        const marker = new google.maps.marker.AdvancedMarkerElement({
            map,
            position: { lat: -34.397, lng: 150.644 },
            title: "Property Location",
        });
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_VALID_API_KEY&callback=initMap" async defer></script>
@endsection
