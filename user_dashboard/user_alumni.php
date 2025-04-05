<!-- Inquiries Modal -->
<div class="modal fade" id="inquiriesModal" tabindex="-1" aria-labelledby="inquiriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inquiriesModalLabel">Inquiries</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="inquiryForm">
                    <!-- Removed Subject and Message fields -->
                    <hr>
                    <h5>Chat with Admin</h5>
                    <div id="chatMessages" style="max-height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                        <!-- Messages will be dynamically loaded here -->
                    </div>
                    <form id="chatForm">
                        <div class="mb-3">
                            <textarea class="form-control" id="chatMessage" rows="3" placeholder="Type your message..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </form>
            </div>
        </div>
    </div>
</div> 