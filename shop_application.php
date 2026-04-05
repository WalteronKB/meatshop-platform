<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: mrbloginpage.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user = null;

$user_query = "SELECT user_name, user_mname, user_lname, user_email, user_contactnum FROM mrb_users WHERE user_id = $user_id LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
if ($user_result && mysqli_num_rows($user_result) > 0) {
    $user = mysqli_fetch_assoc($user_result);
}

$existing_application = null;
$status_query = "SELECT application_id, status, submitted_at, updated_at FROM shop_applications WHERE user_id = $user_id ORDER BY submitted_at DESC LIMIT 1";
$status_result = mysqli_query($conn, $status_query);
if ($status_result) {
    $existing_application = mysqli_fetch_assoc($status_result);
}

$has_submission_block = false;
$is_cooldown_block = false;
$status_notice = '';
$status_notice_type = 'warning';

if ($existing_application && ($existing_application['status'] ?? '') === 'pending') {
    $has_submission_block = true;
    $status_notice = 'You still have an ongoing application.';
    $status_notice_type = 'warning';
} elseif ($existing_application && ($existing_application['status'] ?? '') === 'rejected') {
    $reference_time = $existing_application['updated_at'] ?: $existing_application['submitted_at'];
    if ($reference_time) {
        $elapsed_seconds = time() - strtotime($reference_time);
        $cooldown_seconds = 24 * 60 * 60;
        if ($elapsed_seconds < $cooldown_seconds) {
            $remaining_seconds = $cooldown_seconds - $elapsed_seconds;
            $remaining_hours = (int) ceil($remaining_seconds / 3600);
            $has_submission_block = true;
            $is_cooldown_block = true;
            $status_notice = "Your last application was rejected. You can apply again after 24 hours. Remaining time: about {$remaining_hours} hour(s).";
            $status_notice_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="usersetting.css">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="red-text mb-0">Shop Application Form</h4>
        <button onclick="window.location.href='usersetting.php';" class="btn red-bg text-light">Back to Settings</button>
    </div>

    <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <?php if ($status_notice !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($status_notice_type); ?>">
            <?php echo htmlspecialchars($status_notice); ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_cooldown_block): ?>
    <div class="card">
        <div class="card-body">
            <form action="submit_shop_application.php" method="post" enctype="multipart/form-data">
                <div class="alert alert-info py-2 mb-3">
                    Account email and contact number will be used automatically from your profile.
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="store_name" class="form-label">Store Name</label>
                        <input type="text" class="form-control" id="store_name" name="store_name" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="store_description" class="form-label">Store Description</label>
                    <textarea class="form-control" id="store_description" name="store_description" rows="4" placeholder="Tell us about your products, quality standards, and service." required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="business_permit_no" class="form-label">Business Permit Number</label>
                        <input type="text" class="form-control" id="business_permit_no" name="business_permit_no" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="operating_hours" class="form-label">Operating Hours</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="operating_hours" name="operating_hours" placeholder="Select your weekly schedule" readonly required>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#operatingHoursModal">Set Schedule</button>
                        </div>
                        <small class="text-muted">Example: Monday 8:00 AM - 6:00 PM, Sunday Closed</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tin_no" class="form-label">TIN Number (Optional)</label>
                        <input type="text" class="form-control" id="tin_no" name="tin_no">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="delivery_areas" class="form-label">Delivery Areas (Optional)</label>
                        <input type="text" class="form-control" id="delivery_areas" name="delivery_areas" placeholder="e.g., Quezon City, Makati, Taguig">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="store_address" class="form-label">Store Address</label>
                        <input type="text" class="form-control" id="store_address" name="store_address" placeholder="Enter complete store address in Cavite" required>
                        <small class="text-muted">Only Cavite-based shops are accepted.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address_iframe" class="form-label">Google Maps Link (Optional)</label>
                        <input type="text" class="form-control" id="address_iframe" name="address_iframe" placeholder="Optional: Paste Google Maps embed link, map link, or iframe snippet">
                        <small class="text-muted">No map? You can leave this blank and use the manual address field above.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="border rounded p-2 bg-light">
                        <div class="small text-muted mb-2">Map Preview (Optional)</div>
                        <div id="iframe_preview_placeholder" class="text-muted small">Paste a Google Maps link or iframe snippet to preview it here. If preview fails, your manual Cavite address will still be used.</div>
                        <iframe id="address_iframe_preview" src="" width="100%" height="260" style="border:0; display:none;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="store_logo" class="form-label">Store Logo</label>
                        <input type="file" class="form-control" id="store_logo" name="store_logo" accept=".jpg,.jpeg,.png,.webp" required>
                        <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 3 MB)</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="business_permit_file" class="form-label">Business Permit File</label>
                        <input type="file" class="form-control" id="business_permit_file" name="business_permit_file" accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Allowed: PDF, JPG, JPEG, PNG (max 5 MB)</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">GCash QR (Optional)</label>
                    <input type="file" id="gcash_qr_file" name="gcash_qr_file" accept=".jpg,.jpeg,.png,.webp" class="d-none">
                    <div id="gcash_qr_upload_box" class="border border-2 border-secondary-subtle rounded bg-light d-flex align-items-center justify-content-center text-center position-relative overflow-hidden" style="height: 220px; cursor: pointer;">
                        <img id="gcash_qr_preview" src="" alt="GCash QR Preview" class="w-100 h-100" style="object-fit: contain; display: none;">
                        <div id="gcash_qr_placeholder" class="position-absolute top-50 start-50 translate-middle">
                            <button type="button" id="gcash_qr_upload_button" class="btn btn-outline-dark">Upload Gcash QR</button>
                            <div id="gcash_qr_file_name" class="small text-muted mt-2">No file selected</div>
                        </div>
                        <button type="button" id="gcash_qr_change_button" class="btn btn-sm btn-dark position-absolute bottom-0 end-0 m-2" style="display:none;">Change Gcash QR</button>
                    </div>
                    <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP (max 3 MB)</small>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn red-bg text-light" <?php echo $has_submission_block ? 'disabled' : ''; ?>>
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$is_cooldown_block): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <div id="applicationSuccessToast" class="toast text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Shop application submitted successfully. Your application is now pending review.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="modal fade" id="operatingHoursModal" tabindex="-1" aria-labelledby="operatingHoursModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operatingHoursModalLabel">Set Operating Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Uncheck a day to mark it as Closed.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Open</th>
                                <th style="width: 30%;">Day</th>
                                <th style="width: 25%;">From</th>
                                <th style="width: 25%;">To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-day-row="Monday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Monday" checked></td>
                                <td>Monday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Tuesday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Tuesday" checked></td>
                                <td>Tuesday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Wednesday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Wednesday" checked></td>
                                <td>Wednesday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Thursday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Thursday" checked></td>
                                <td>Thursday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Friday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Friday" checked></td>
                                <td>Friday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Saturday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Saturday" checked></td>
                                <td>Saturday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00"></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00"></td>
                            </tr>
                            <tr data-day-row="Sunday">
                                <td><input class="form-check-input day-open" type="checkbox" data-day="Sunday"></td>
                                <td>Sunday</td>
                                <td><input type="time" class="form-control form-control-sm day-open-time" value="08:00" disabled></td>
                                <td><input type="time" class="form-control form-control-sm day-close-time" value="18:00" disabled></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn red-bg text-light" id="applyOperatingHours">Apply Schedule</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var iframeInput = document.getElementById('address_iframe');
    var iframePreview = document.getElementById('address_iframe_preview');
    var placeholder = document.getElementById('iframe_preview_placeholder');
    var operatingHoursInput = document.getElementById('operating_hours');
    var applyOperatingHoursButton = document.getElementById('applyOperatingHours');
    var operatingHoursModalElement = document.getElementById('operatingHoursModal');
    var operatingHoursModal = operatingHoursModalElement ? bootstrap.Modal.getOrCreateInstance(operatingHoursModalElement) : null;
    var gcashQrInput = document.getElementById('gcash_qr_file');
    var gcashQrUploadBox = document.getElementById('gcash_qr_upload_box');
    var gcashQrUploadButton = document.getElementById('gcash_qr_upload_button');
    var gcashQrPreview = document.getElementById('gcash_qr_preview');
    var gcashQrPlaceholder = document.getElementById('gcash_qr_placeholder');
    var gcashQrFileName = document.getElementById('gcash_qr_file_name');
    var gcashQrChangeButton = document.getElementById('gcash_qr_change_button');

    function formatTo12Hour(timeValue) {
        if (!timeValue) {
            return '';
        }

        var parts = timeValue.split(':');
        var hours = parseInt(parts[0], 10);
        var minutes = parts[1] || '00';
        var period = hours >= 12 ? 'PM' : 'AM';
        var hour12 = hours % 12;
        if (hour12 === 0) {
            hour12 = 12;
        }

        return hour12 + ':' + minutes + ' ' + period;
    }

    function syncDayRowState(row) {
        var openCheckbox = row.querySelector('.day-open');
        var openTimeInput = row.querySelector('.day-open-time');
        var closeTimeInput = row.querySelector('.day-close-time');
        var isOpen = openCheckbox.checked;

        openTimeInput.disabled = !isOpen;
        closeTimeInput.disabled = !isOpen;
    }

    function buildOperatingHoursValue() {
        var rows = document.querySelectorAll('[data-day-row]');
        var scheduleLines = [];

        rows.forEach(function (row) {
            var day = row.getAttribute('data-day-row');
            var isOpen = row.querySelector('.day-open').checked;

            if (!isOpen) {
                scheduleLines.push(day + ': Closed');
                return;
            }

            var openTime = row.querySelector('.day-open-time').value;
            var closeTime = row.querySelector('.day-close-time').value;
            scheduleLines.push(day + ': ' + formatTo12Hour(openTime) + ' - ' + formatTo12Hour(closeTime));
        });

        return scheduleLines.join(' | ');
    }

    function extractSrc(value) {
        var trimmed = (value || '').trim();
        if (!trimmed) {
            return '';
        }

        var iframeMatch = trimmed.match(/src\s*=\s*['\"]([^'\"]+)['\"]/i);
        if (iframeMatch && iframeMatch[1]) {
            return iframeMatch[1];
        }

        var lower = trimmed.toLowerCase();
        if (lower.indexOf('google.com/maps/embed') !== -1 || lower.indexOf('googleusercontent.com/maps') !== -1) {
            return trimmed;
        }

        if (lower.indexOf('google.com/maps') !== -1 || lower.indexOf('maps.app.goo.gl') !== -1 || lower.indexOf('goo.gl/maps') !== -1) {
            return 'https://www.google.com/maps?output=embed&q=' + encodeURIComponent(trimmed);
        }

        return trimmed;
    }

    function updatePreview() {
        var src = extractSrc(iframeInput.value);
        if (!src) {
            iframePreview.style.display = 'none';
            iframePreview.src = '';
            placeholder.style.display = 'block';
            return;
        }

        iframePreview.src = src;
        iframePreview.style.display = 'block';
        placeholder.style.display = 'none';
    }

    document.querySelectorAll('[data-day-row]').forEach(function (row) {
        var checkbox = row.querySelector('.day-open');
        syncDayRowState(row);
        checkbox.addEventListener('change', function () {
            syncDayRowState(row);
        });
    });

    if (applyOperatingHoursButton) {
        applyOperatingHoursButton.addEventListener('click', function () {
            operatingHoursInput.value = buildOperatingHoursValue();
            if (operatingHoursModal) {
                operatingHoursModal.hide();
            }
        });
    }

    if (operatingHoursInput && !operatingHoursInput.value) {
        operatingHoursInput.value = buildOperatingHoursValue();
    }

    if (gcashQrUploadButton) {
        gcashQrUploadButton.addEventListener('click', function (event) {
            event.preventDefault();
            gcashQrInput.click();
        });
    }

    if (gcashQrChangeButton) {
        gcashQrChangeButton.addEventListener('click', function (event) {
            event.preventDefault();
            gcashQrInput.click();
        });
    }

    if (gcashQrUploadBox) {
        gcashQrUploadBox.addEventListener('click', function (event) {
            if (event.target === gcashQrUploadButton) {
                return;
            }
            gcashQrInput.click();
        });
    }

    if (gcashQrInput) {
        gcashQrInput.addEventListener('change', function () {
            var file = gcashQrInput.files && gcashQrInput.files[0] ? gcashQrInput.files[0] : null;

            if (!file) {
                gcashQrPreview.src = '';
                gcashQrPreview.style.display = 'none';
                gcashQrPlaceholder.style.display = 'block';
                gcashQrFileName.textContent = 'No file selected';
                gcashQrChangeButton.style.display = 'none';
                return;
            }

            gcashQrFileName.textContent = file.name;
            var previewUrl = URL.createObjectURL(file);
            gcashQrPreview.src = previewUrl;
            gcashQrPreview.style.display = 'block';
            gcashQrPlaceholder.style.display = 'none';
            gcashQrChangeButton.style.display = 'inline-block';
        });
    }

    var params = new URLSearchParams(window.location.search);
    if (params.get('success') === 'true') {
        var successToastElement = document.getElementById('applicationSuccessToast');
        if (successToastElement) {
            var successToast = bootstrap.Toast.getOrCreateInstance(successToastElement, {
                delay: 5000
            });
            successToast.show();
        }
    }

    if (iframeInput && iframePreview && placeholder) {
        iframeInput.addEventListener('input', updatePreview);
        updatePreview();
    }
});
</script>
</body>
</html>
