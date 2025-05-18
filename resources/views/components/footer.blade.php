<footer class="bg-white border-top mt-5 shadow-sm">
    <div class="container py-4">
        <div class="row text-center text-md-start">
            <!-- Brand -->
            <div class="col-md-4 mb-4 mb-md-0">
                <h6 class="fw-bold text-dark">DEFENDER</h6>
                <p class="text-muted small mb-0">Your trusted platform for financial and property investments.</p>
            </div>

            <!-- Navigation -->
            <div class="col-md-4 mb-4 mb-md-0">
                <h6 class="fw-bold text-dark">Navigation</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('home') }}" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="{{ route('properties.index') }}" class="text-muted text-decoration-none">Properties</a></li>
                    <li><a href="{{ route('properties.index') }}" class="text-muted text-decoration-none">My Details</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-4">
                <h6 class="fw-bold text-dark">Contact</h6>
                <p class="text-muted small mb-1">support@defender.com</p>
                <p class="text-muted small">+353 123 4567</p>
            </div>
        </div>
    </div>

    <div class="text-center py-3 border-top small text-muted bg-light">
        © {{ date('Y') }} Defender. All rights reserved.
    </div>
</footer>
