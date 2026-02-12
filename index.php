<?php
require 'auth.php';
require 'db.php';

// --- Get Filter Parameters for Title & Sidebar ---
$booth = $_GET['booth'] ?? '';
$min_age = $_GET['min_age'] ?? '';
$max_age = $_GET['max_age'] ?? '';
$gender = $_GET['gender'] ?? '';
$page = $_GET['page'] ?? '';
$filter_by = $_GET['filter_by'] ?? '';

// Build Query String for Export Links
$qs = http_build_query([
    'booth' => $booth,
    'min_age' => $min_age,
    'max_age' => $max_age,
    'gender' => $gender,
    'page' => $page
]);

$page_title = "Filtered Voters Data (OTN 2026)";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mannargudi - OTN 2026 Analysis</title>

    <!-- Main Stylesheet (Local) -->
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <!-- DataTables Core & Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        /* Specific override for sidebar scrolling if list is long */
        .nav-list {
            overflow-y: auto;
            flex-grow: 1;
        }
        /* CRUD Action Buttons */
        .btn-action {
            padding: 4px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }
        .btn-delete {
            background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
            color: white;
        }
        .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(245, 87, 108, 0.4);
        }
        .btn-add-new {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
            transition: all 0.2s ease;
        }
        .btn-add-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(17, 153, 142, 0.4);
        }
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: linear-gradient(145deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 25px;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .modal-header h2 {
            color: #fff;
            margin: 0;
            font-size: 1.5rem;
        }
        .modal-close {
            background: none;
            border: none;
            color: #aaa;
            font-size: 28px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close:hover {
            color: #f5576c;
        }
        .modal-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .modal-form .form-group {
            display: flex;
            flex-direction: column;
        }
        .modal-form .form-group.full-width {
            grid-column: span 2;
        }
        .modal-form label {
            color: #aaa;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .modal-form input,
        .modal-form select {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 14px;
        }
        .modal-form input:focus,
        .modal-form select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        .modal-form select option {
            background: #1a1a2e;
            color: #fff;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-modal-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-modal-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #aaa;
            padding: 10px 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            cursor: pointer;
        }
        .action-buttons-container {
            white-space: nowrap;
        }
        /* EPIC Search Button */
        .epic-search-container {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        .epic-search-container input {
            flex: 1;
        }
        .btn-epic-search {
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .btn-epic-search:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 10px rgba(0, 180, 219, 0.4);
        }
        .btn-epic-search:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .search-status {
            font-size: 11px;
            margin-top: 4px;
            min-height: 14px;
        }
        .search-status.success {
            color: #38ef7d;
        }
        .search-status.error {
            color: #f5576c;
        }
    </style>
</head>

<body>

    <div class="main-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <!-- <h2>SIDEBAR</h2> -->
            <img class="minister-logo" src="minister.png" alt="">
            
            <!-- 1. SEARCH INPUT -->
            <div class="sidebar-search-wrapper">
                <input type="text" id="boothSearchInput" placeholder="🔍 Search Booth..." autocomplete="off">
                <span id="clearSearchBtn" class="search-clear-btn">&times;</span>
            </div>

        <!-- 2. NAVIGATION SHORTCUTS -->
            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                <!-- ANALYSIS LINK -->
                <a href="index.php" 
                    style="display: block; background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.4rem; font-family: var(--fontBody); border: 1px solid rgba(255,255,255,0.3);">
                    📈 OTN 2026 VALIDATION
                </a>

                <!-- BULK IMPORT LINK -->
                <a href="bulk_import.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem;">
                    📥 Bulk Import
                </a>

                <!-- BULK OTN UPDATE LINK -->
                <a href="bulk_otn.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem;">
                    🎯 Bulk OTN Update
                </a>



                <!-- EPIC Mobile Check LINK -->
                <a href="epic_mobile_check.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem;">
                    🔍 EPIC Mobile Check
                </a>

                <!-- Mobile Name Check LINK -->
                <a href="mobile_name_check.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem;">
                    📱 Mobile Name Check
                </a>

                <!-- MOBILE UNIQUE LINK (Inactive) -->
                <a href="mobile_unique.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem; margin-top: 8px;">
                     Mobile Unique
                </a>

                <!-- NON UNIQUE MOBILE LINK (Inactive) -->
                <a href="non_mobile_unique.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem; margin-top: 8px;">
                     Non Unique Mobile
                </a>

                <!-- TWO TIMES MOBILE LINK (Inactive) -->
                <a href="two_times_mobile.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem; margin-top: 8px;">
                     Two Times
                </a>

                <!-- THREE TIMES MOBILE LINK (Inactive) -->
                <a href="three_times_mobile.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem; margin-top: 8px;">
                     Three Times
                </a>

                <!-- PENDING OTN LIST LINK (Inactive) -->
                <a href="pending_otn_list.php" 
                    style="display: block; background: rgba(255,255,255,0.05); color: #ccc; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 500; font-size: 1.2rem; margin-top: 8px;">
                     Pending OTN List
                </a>

                <!-- BOOTH VISIT PDF LINK -->
                <a href="booth_visit_pdf.php" 
                    style="display: block; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; text-decoration: none; padding: 12px 15px; border-radius: 6px; text-align: center; font-weight: 600; font-size: 1.2rem; margin-top: 8px;">
                    🚶 Booth Visit
                </a>
            </div>


            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 0;">

            <!-- 3. SCROLLABLE BOOTH LIST -->
            <ul class="nav-list" id="boothList">
                <li onclick="filterByBooth('')">Loading...</li>
            </ul>

            <!-- 4. FOOTER: ADMIN & LOGOUT -->
            <div style="margin-top:auto; border-top:1px solid rgba(255,255,255,0.2); padding:1rem;">
                 <a href="logout.php"
                    style="display:block; color:white; text-decoration:none; padding:12px 15px; text-align:center; font-size:1.4rem; font-family:var(--fontBody); font-weight:500; background:rgba(255,255,255,0.1); border-radius:6px;">
                    Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="content-area">
            <header class="top-header">
                <!-- <h1>Mannargudi - 167</h1> -->
                <img class="dmk-logo" src="logo_dmk.png" alt="">
                <div class="constituency">
                    <h2 class="constituency-name">Mannargudi-167</h2>
                    <span class="tamil-name">(மன்னார்குடி)</span>
                </div>
                <img class="mannai-logo" src="logo_mannai.png" alt="">
            </header>

            <main>
                <div class="dashboard-header">
                    <h1>OTN 2026 DASHBOARD ANALYTICS</h1>
                    <span class="booth-badge" id="activeBoothLabel">Active Booth: All</span>
                </div>

                <!-- Stats Grid -->
                <section class="analysis">
                    <div class='total-votes-box'>
                        <span class='label'>Total Votes</span>
                        <span class='number' id="stat-total">0</span>
                    </div>
                    <div class='demographics-grid'>
                        <div class='stat-box'><span>Male</span><strong id="stat-male">0</strong></div>
                        <div class='stat-box'><span>Female</span><strong id="stat-female">0</strong></div>
                        <div class='stat-box'><span>Transgender</span><strong id="stat-trans">0</strong></div>
                        <div class='stat-box'><span>Families</span><strong id="stat-families">0</strong></div>
                        <div class='stat-box'><span>18-21 Yrs</span><strong id="stat-18-21">0</strong></div>
                        <div class='stat-box'><span>22-35 Yrs</span><strong id="stat-22-35">0</strong></div>
                        <div class='stat-box'><span>36-49 Yrs</span><strong id="stat-36-49">0</strong></div>
                        <div class='stat-box'><span>50+ Yrs</span><strong id="stat-50-plus">0</strong></div>
                    </div>
                </section>

                <!-- Party Stats Row -->
                <section class="party-stats-row">
                    <div class="party-stat-box" onclick="openPartyFilteredView('DMK')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">DMK</span>
                            <strong class="party-count" id="stat-dmk">0</strong>
                        </div>
                        <img src="party_logos/dmk.png" alt="DMK" class="party-logo">
                    </div>
                    <div class="party-stat-box" onclick="openPartyFilteredView('ADMK')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">ADMK</span>
                            <strong class="party-count" id="stat-admk">0</strong>
                        </div>
                        <img src="party_logos/admk.png" alt="ADMK" class="party-logo">
                    </div>
                    <div class="party-stat-box" onclick="openPartyFilteredView('TVK')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">TVK</span>
                            <strong class="party-count" id="stat-tvk">0</strong>
                        </div>
                        <img src="party_logos/tvk.png" alt="TVK" class="party-logo">
                    </div>
                    <div class="party-stat-box" onclick="openPartyFilteredView('OTN')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">OTN</span>
                            <strong class="party-count" id="stat-otn">0</strong>
                        </div>
                        <img src="party_logos/otn.png" alt="OTN" class="party-logo">
                    </div>
                    <div class="party-stat-box" onclick="openPartyFilteredView('Others')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">Others</span>
                            <strong class="party-count" id="stat-others">0</strong>
                        </div>
                        <img src="party_logos/others.png" alt="Others" class="party-logo">
                    </div>
                    <!-- Beneficiaries Card -->
                    <div class="party-stat-box" onclick="openPartyFilteredView('Beneficiaries')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">BENEFICIARIES</span>
                            <strong class="party-count" id="stat-beneficiaries">0</strong>
                        </div>
                        <img src="party_logos/tamilnadu.png" alt="TN" class="party-logo">
                    </div>
                    <!-- Postal Card -->
                    <div class="party-stat-box" onclick="openPartyFilteredView('Postal')" style="cursor:pointer;">
                        <div class="party-data">
                            <span class="party-label">POSTAL</span>
                            <strong class="party-count" id="stat-postal">0</strong>
                        </div>
                        <img src="party_logos/postal.png" alt="Postal" class="party-logo">
                    </div>
                </section>

                <!-- Filters -->
                <section class="manipulation">
                    <div class="advanced-filter-panel">
                        <div class="input-group"><label>Age Min</label><input type="number" id="advMinAge"
                                placeholder="18"></div>
                        <div class="input-group"><label>Age Max</label><input type="number" id="advMaxAge"
                                placeholder="100"></div>
                        <div class="input-group"><label>Gender</label>
                            <select id="advGender">
                                <option value="">All</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Transgender">Transgender</option>
                            </select>
                        </div>
                        <div class="input-group"><label>Booth</label><input type="text" id="advBooth"
                                placeholder="Ex: 140"></div>
                        <div class="input-group"><label>Page</label><input type="text" id="advPage" placeholder="Ex: 1">
                        </div>
                        <div class="input-group"><label>Party</label>
                            <select id="advParty">
                                <option value="">All</option>
                                <option value="DMK">DMK</option>
                                <option value="ADMK">ADMK</option>
                                <option value="TVK">TVK</option>
                                <option value="OTN">OTN</option>
                                <option value="OTHERS">OTHERS</option>
                            </select>
                        </div>
                        <div class="input-group"><label>Gov Ben</label>
                            <select id="advGovBen">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        <div class="input-group"><label>Postal</label>
                            <select id="advPostal">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" id="btnSearch">Search</button>
                        <button class="btn btn-secondary" id="btnClear">Clear</button>
                    </div>
                    <div class="export_options"></div>
                        <button class="btn-add-new" id="btnAddNew">➕ Add New Record</button>
                    </section>

                <!-- Table -->
                <section class="table_container">
                    <table id="voterTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>S.No</th>
                                <th>EPIC No</th>
                                <th>Name (Ta)</th>
                                <th>Name (En)</th>
                                <th>Rel Type</th>
                                <th>Rel Name (Ta)</th>
                                <th>Rel Name (En)</th>
                                <th>H.No</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Booth</th>
                                <th>Page</th>
                                <!-- NEW COLUMNS -->
                                <th>Mobile</th>
                                <th>Party</th>
                                <th>Gov Ben</th>
                                <th>Postal</th>
                                <th>Union</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </section>
            </main>

            <footer>&copy; 2026 Election Analytics Dashboard (OTN)</footer>
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        let table;
        const fontUrl = 'https://cdn.jsdelivr.net/gh/google/fonts@main/ofl/hindmadurai/HindMadurai-Regular.ttf';
        const safeNum = (val) => isNaN(Number(val)) ? '0' : Number(val).toLocaleString();

        $(document).ready(function () {
            loadSidebar();
            loadCustomFontAndInitTable();
            fetchDashboardStats(); // Initial stats load
        });

        /* =========================================
           1. SIDEBAR SEARCH & FILTER LOGIC
           ========================================= */

        // Search Input Logic
        $('#boothSearchInput').on('keyup', function () {
            var value = $(this).val().toLowerCase();

            // Show/Hide Clear Button
            if (value.length > 0) {
                $('#clearSearchBtn').show();
            } else {
                $('#clearSearchBtn').hide();
            }

            // Filter List
            $("#boothList li").filter(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1 || value === "");
            });
        });

        // Clear Button Logic
        $('#clearSearchBtn').on('click', function () {
            $('#boothSearchInput').val('');
            $(this).hide();
            $("#boothList li").show();
            $('#boothSearchInput').focus();
        });

        // Load Booth List from DB
        function loadSidebar() {
            $.getJSON('get_booths.php', function (data) {
                let html = '<li onclick="filterByBooth(\'\')">All Booths</li>';
                data.forEach(b => html += `<li onclick="filterByBooth('${b}')">Booth ${b}</li>`);
                $('#boothList').html(html);
            });
        }

        // Main Filter Function (Updates Inputs, Table, and Stats)
        window.filterByBooth = function (booth) {
            $('#advBooth').val(booth);
            $('#activeBoothLabel').text(booth ? `Active Booth: ${booth}` : 'Active Booth: All');
            if (table) table.draw();
            fetchDashboardStats();
        }

        /* =========================================
           2. STATS ANALYTICS LOGIC
           ========================================= */
        function calcPct(val, total) {
            if (!total || total === 0) return ' <span class="stat-pct">(0%)</span>';
            let p = ((val / total) * 100).toFixed(1);
            return ' <span class="stat-pct">(' + p + '%)</span>';
        }

        function fetchDashboardStats() {
            let filters = {
                booth: $('#advBooth').val(),
                min_age: $('#advMinAge').val(),
                max_age: $('#advMaxAge').val(),
                gender: $('#advGender').val(),
                page: $('#advPage').val(),
                party: $('#advParty').val(),
                gov_ben: $('#advGovBen').val(),
                postal: $('#advPostal').val()
            };

            $('#stat-total').css('opacity', '0.5');

            $.ajax({
                url: 'get_stats.php',
                method: 'GET',
                data: filters,
                dataType: 'json',
                success: function (d) {
                    if (!d) return;
                    let t = d.total || 0;
                    $('#stat-total').text(safeNum(t));

                    $('#stat-male').html(safeNum(d.male) + calcPct(d.male, t));
                    $('#stat-female').html(safeNum(d.female) + calcPct(d.female, t));
                    $('#stat-trans').html(safeNum(d.trans) + calcPct(d.trans, t));
                    $('#stat-families').text(safeNum(d.families));

                    $('#stat-18-21').html(safeNum(d.age_18_21) + calcPct(d.age_18_21, t));
                    $('#stat-22-35').html(safeNum(d.age_22_35) + calcPct(d.age_22_35, t));
                    $('#stat-36-49').html(safeNum(d.age_36_49) + calcPct(d.age_36_49, t));
                    $('#stat-50-plus').html(safeNum(d.age_50_plus) + calcPct(d.age_50_plus, t));

                    $('#stat-total').css('opacity', '1');
                },
                error: function () {
                    $('#stat-total').css('opacity', '1');
                }
            });

            // Also fetch party stats
            fetchPartyStats(filters);
        }

        // Open party voters filtered view
        window.openPartyFilteredView = function (filterType) {
            // Build URL with current filters plus the clicked filter type
            let url = 'view_filtered.php?';
            
            // Add current filter values
            const booth = $('#advBooth').val();
            const minAge = $('#advMinAge').val();
            const maxAge = $('#advMaxAge').val();
            const gender = $('#advGender').val();
            const page = $('#advPage').val();
            
            if (booth) url += `booth=${encodeURIComponent(booth)}&`;
            if (minAge) url += `min_age=${encodeURIComponent(minAge)}&`;
            if (maxAge) url += `max_age=${encodeURIComponent(maxAge)}&`;
            if (gender) url += `gender=${encodeURIComponent(gender)}&`;
            if (page) url += `page=${encodeURIComponent(page)}&`;
            
            // Add the specific party/beneficiaries/postal filter
            if (filterType === 'Beneficiaries') {
                url += `gov_ben=YES&`;
            } else if (filterType === 'Postal') {
                url += `postal=YES&`;
            } else {
                // Party filter (DMK, ADMK, TVK, OTN, Others)
                url += `party=${encodeURIComponent(filterType)}&`;
            }
            
            url = url.slice(0, -1); // Remove trailing &
            window.open(url, '_blank');
        }

        /* =========================================
           2b. PARTY STATS LOGIC
           ========================================= */
        function fetchPartyStats(filters) {
            $.ajax({
                url: 'get_party_stats.php',
                method: 'GET',
                data: filters, // Pass filters to party stats too!
                dataType: 'json',
                success: function (d) {
                    if (!d) return;
                    let t = d.total || 0; 

                    $('#stat-dmk').html(safeNum(d.dmk || 0) + calcPct(d.dmk || 0, t));
                    $('#stat-admk').html(safeNum(d.admk || 0) + calcPct(d.admk || 0, t));
                    $('#stat-tvk').html(safeNum(d.tvk || 0) + calcPct(d.tvk || 0, t));
                    $('#stat-otn').html(safeNum(d.otn || 0) + calcPct(d.otn || 0, t));
                    $('#stat-others').html(safeNum(d.others || 0) + calcPct(d.others || 0, t));
                    $('#stat-beneficiaries').html(safeNum(d.beneficiaries || 0) + calcPct(d.beneficiaries || 0, t));
                    $('#stat-postal').html(safeNum(d.postal || 0) + calcPct(d.postal || 0, t));
                },
                error: function () {
                    console.log('Party stats fetch error');
                }
            });
        }

        /* =========================================
           3. DATATABLE & FONT LOGIC
           ========================================= */
        async function loadCustomFontAndInitTable() {
            try {
                const resp = await fetch(fontUrl);
                const blob = await resp.blob();
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = function () {
                    const b64 = reader.result.split(',')[1];
                    pdfMake.vfs['HindMadurai-Regular.ttf'] = b64;
                    pdfMake.fonts = {
                        HindMadurai: {
                            normal: 'HindMadurai-Regular.ttf',
                            bold: 'HindMadurai-Regular.ttf'
                        },
                        Roboto: {
                            normal: 'Roboto-Regular.ttf',
                            bold: 'Roboto-Medium.ttf'
                        }
                    };
                    initDataTable();
                }
            } catch (e) {
                initDataTable();
            }
        }

        function initDataTable() {
            // A. Clone Header for Inline Search
            $('#voterTable thead tr')
                .clone(true)
                .addClass('filters')
                .appendTo('#voterTable thead');

            // B. Initialize DataTable
            table = $('#voterTable').DataTable({
                orderCellsTop: true,
                fixedHeader: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'fetch_data.php',
                    type: 'POST',
                    data: function (d) {
                        d.min_age = $('#advMinAge').val();
                        d.max_age = $('#advMaxAge').val();
                        d.gender = $('#advGender').val();
                        d.booth = $('#advBooth').val();
                        d.page = $('#advPage').val();
                        d.party = $('#advParty').val();
                        d.gov_ben = $('#advGovBen').val();
                        d.postal = $('#advPostal').val();
                    }
                },
                columns: [
                    { data: "id", title: "ID" }, 
                    { data: "sno", title: "S.No" },
                    { data: "epic", title: "EPIC No" },
                    { data: "name_ta", title: "Name (Ta)", className: "tamil-text" },
                    { data: "name_en", title: "Name (En)" },
                    { data: "rel_type", title: "Rel Type" },
                    { data: "rel_name_ta", title: "Rel Name (Ta)", className: "tamil-text" },
                    { data: "rel_name_en", title: "Rel Name (En)" },
                    { data: "house_no", title: "H.No" },
                    { data: "age", title: "Age" }, 
                    { data: "gender", title: "Gender" },
                    { data: "booth", title: "Booth" }, 
                    { data: "page", title: "Page" },
                    // NEW COLUMNS
                    { data: "mobile", title: "Mobile" },
                    { data: "party", title: "Party" },
                    { data: "government_beneficiaries", title: "Gov Ben" },
                    { data: "postal", title: "Postal" },
                    { data: "union_name", title: "Union" },
                    // ACTIONS COLUMN
                    {
                        data: null,
                        title: "Actions",
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return '<div class="action-buttons-container">' +
                                '<button class="btn-action btn-edit" onclick="editRow(' + row.id + ')" title="Edit">✏️ Edit</button>' +
                                '<button class="btn-action btn-delete" onclick="deleteRow(' + row.id + ')" title="Delete">🗑️ Delete</button>' +
                                '</div>';
                        }
                    }
                ],
                dom: 'lrtip',
                pageLength: 10,

                // C. Inline Search Logic
                initComplete: function () {
                    var api = this.api();
                    api.columns().eq(0).each(function (colIdx) {
                        var cell = $('.filters th').eq($(api.column(colIdx).header()).index());
                        var title = $(cell).text();
                        $(cell).html('<input type="text" placeholder="' + title + '" />');
                        $('input', $(cell))
                            .off('keyup change')
                            .on('keyup change', function (e) {
                                e.stopPropagation();
                                $(this).attr('title', $(this).val());
                                api.column(colIdx).search(this.value).draw();
                            });
                    });
                }
            });

            // D. CUSTOM EXPORT BUTTONS (PDF, EXCEL, PRINT)
            new $.fn.dataTable.Buttons(table, {
                buttons: [
                // 1. EXPORT PDF (Single File Preview)
                {
                    text: '<img src="party_logos/pdf_logo.png" height="20" style="vertical-align:middle; margin-right:5px;"> Export PDF (Preview)',
                    className: 'btn-custom btn-pdf',
                    action: function (e, dt, node, config) {
                        let params = $.param({
                            booth: $('#advBooth').val(),
                            min_age: $('#advMinAge').val(),
                            max_age: $('#advMaxAge').val(),
                            gender: $('#advGender').val(),
                            page: $('#advPage').val(),
                            party: $('#advParty').val(),
                            gov_ben: $('#advGovBen').val(),
                            postal: $('#advPostal').val(),
                            type: 'pdf',
                            source: 'otn'
                        });
                        window.open('export_all.php?' + params, '_blank');
                    }
                },
                {
                    text: '<img src="party_logos/excel_logo.png" height="20" style="vertical-align:middle; margin-right:5px;"> Export Excel',
                    className: 'btn-custom btn-excel',
                    action: function (e, dt, node, config) {
                        let params = $.param({
                            booth: $('#advBooth').val(),
                            min_age: $('#advMinAge').val(),
                            max_age: $('#advMaxAge').val(),
                            gender: $('#advGender').val(),
                            page: $('#advPage').val(),
                            party: $('#advParty').val(),
                            gov_ben: $('#advGovBen').val(),
                            postal: $('#advPostal').val(),
                            type: 'excel',
                            source: 'otn'
                        });
                        window.location.href = 'export_all.php?' + params;
                    }
                },
                {
                    text: '<img src="party_logos/batch_logo.png" height="20" style="vertical-align:middle; margin-right:5px;"> Batch PDF (Filtered)',
                    className: 'btn-custom btn-danger',
                    action: function (e, dt, node, config) {
                        let params = $.param({
                            booth: $('#advBooth').val(),
                            min_age: $('#advMinAge').val(),
                            max_age: $('#advMaxAge').val(),
                            gender: $('#advGender').val(),
                            page: $('#advPage').val(),
                            party: $('#advParty').val(),
                            gov_ben: $('#advGovBen').val(),
                            postal: $('#advPostal').val(),
                            source: 'otn'
                        });
                        window.open('batch_pdf_generator.php?' + params, '_blank');
                    }
                },
                {
                    text: '<img src="party_logos/excel_logo.png" height="20" style="vertical-align:middle; margin-right:5px;"> Batch CSV Generator',
                    className: 'btn-custom btn-success',
                    action: function (e, dt, node, config) {
                        let params = $.param({
                            booth: $('#advBooth').val(),
                            min_age: $('#advMinAge').val(),
                            max_age: $('#advMaxAge').val(),
                            gender: $('#advGender').val(),
                            page: $('#advPage').val(),
                            party: $('#advParty').val(),
                            gov_ben: $('#advGovBen').val(),
                            postal: $('#advPostal').val()
                        });
                        window.open('batch_csv_generator.php?' + params, '_blank');
                    }
                }
                ]
            }).container().appendTo($('.export_options'));

            // E. External Filter Button Events
            $('#btnSearch').click(function () {
                table.draw();
                fetchDashboardStats();
            });
            $('#btnClear').click(function () {
                $('input, select').val('');
                $('#activeBoothLabel').text('Active Booth: All');
                table.draw();
                fetchDashboardStats();
            });
        }
    </script>

    <!-- CRUD Modal -->
    <div class="modal-overlay" id="crudModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Record</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form class="modal-form" id="crudForm">
                <input type="hidden" id="recordId" name="id">
                
                <div class="form-group">
                    <label>S.No *</label>
                    <input type="number" id="formSno" name="sno" required>
                </div>
                <div class="form-group">
                    <label>EPIC No *</label>
                    <div class="epic-search-container">
                        <input type="text" id="formEpic" name="epic" required maxlength="20">
                        <button type="button" class="btn-epic-search" id="btnEpicSearch" onclick="searchEpicInSir2025()" style="display: none;">🔍 Search</button>
                    </div>
                    <div class="search-status" id="epicSearchStatus"></div>
                </div>
                <div class="form-group">
                    <label>Name (Tamil) *</label>
                    <input type="text" id="formNameTa" name="name_ta" required>
                </div>
                <div class="form-group">
                    <label>Name (English) *</label>
                    <input type="text" id="formNameEn" name="name_en" required>
                </div>
                <div class="form-group">
                    <label>Relation Type *</label>
                    <select id="formRelType" name="rel_type" required>
                        <option value="">Select</option>
                        <option value="Father">Father</option>
                        <option value="Husband">Husband</option>
                        <option value="Mother">Mother</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rel Name (Tamil) *</label>
                    <input type="text" id="formRelNameTa" name="rel_name_ta" required>
                </div>
                <div class="form-group">
                    <label>Rel Name (English) *</label>
                    <input type="text" id="formRelNameEn" name="rel_name_en" required>
                </div>
                <div class="form-group">
                    <label>House No *</label>
                    <input type="text" id="formHouseNo" name="house_no" required maxlength="50">
                </div>
                <div class="form-group">
                    <label>Age *</label>
                    <input type="number" id="formAge" name="age" required min="18" max="120">
                </div>
                <div class="form-group">
                    <label>Gender *</label>
                    <select id="formGender" name="gender" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Transgender">Transgender</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Booth *</label>
                    <input type="text" id="formBooth" name="booth" required maxlength="10">
                </div>
                <div class="form-group">
                    <label>Page *</label>
                    <input type="text" id="formPage" name="page" required maxlength="10">
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" id="formMobile" name="mobile" maxlength="10" pattern="[0-9]{10}" placeholder="10 digits">
                </div>
                <div class="form-group">
                    <label>Party</label>
                    <select id="formParty" name="party">
                        <option value="">Select</option>
                        <option value="DMK">DMK</option>
                        <option value="ADMK">ADMK</option>
                        <option value="TVK">TVK</option>
                        <option value="OTN">OTN</option>
                        <option value="OTHERS">OTHERS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gov Beneficiaries</label>
                    <select id="formGovBen" name="government_beneficiaries">
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Postal</label>
                    <select id="formPostal" name="postal">
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
            </form>
            <div class="modal-actions">
                <button class="btn-modal-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-modal-save" onclick="saveRecord()">Save</button>
            </div>
        </div>
    </div>

    <script>
        /* =========================================
           CRUD OPERATIONS
           ========================================= */
        let currentEditId = null;

        // Add New Record Button
        $('#btnAddNew').click(function() {
            currentEditId = null;
            $('#modalTitle').text('Add New Record');
            $('#crudForm')[0].reset();
            $('#recordId').val('');
            $('#btnEpicSearch').show(); // Show search button for Add mode
            $('#epicSearchStatus').text('').removeClass('success error');
            openModal();
        });

        function openModal() {
            $('#crudModal').addClass('active');
        }

        function closeModal() {
            $('#crudModal').removeClass('active');
            currentEditId = null;
            $('#btnEpicSearch').hide(); // Hide search button when closing
            $('#epicSearchStatus').text('').removeClass('success error');
        }

        // Search EPIC in otn_2026 and auto-fill form
        function searchEpicInSir2025() {
            const epic = $('#formEpic').val().trim();
            const statusEl = $('#epicSearchStatus');
            const btnSearch = $('#btnEpicSearch');
            
            if (!epic) {
                statusEl.text('Please enter EPIC number').removeClass('success').addClass('error');
                return;
            }
            
            // Show loading state
            btnSearch.prop('disabled', true).text('⏳ Searching...');
            statusEl.text('').removeClass('success error');
            
            $.ajax({
                url: 'search_otn_2026.php',
                method: 'GET',
                data: { epic: epic },
                dataType: 'json',
                success: function(response) {
                    btnSearch.prop('disabled', false).text('🔍 Search');
                    
                    if (response.success) {
                        const d = response.data;
                        // Populate form fields
                        $('#formSno').val(d.sno || '');
                        $('#formNameTa').val(d.name_ta || '');
                        $('#formNameEn').val(d.name_en || '');
                        $('#formRelType').val(d.rel_type || '');
                        $('#formRelNameTa').val(d.rel_name_ta || '');
                        $('#formRelNameEn').val(d.rel_name_en || '');
                        $('#formHouseNo').val(d.house_no || '');
                        $('#formAge').val(d.age || '');
                        $('#formGender').val(d.gender || '');
                        $('#formBooth').val(d.booth || '');
                        $('#formPage').val(d.page || '');
                        
                        statusEl.text('✅ Found! Form auto-filled from otn_2026').removeClass('error').addClass('success');
                    } else {
                        statusEl.text('❌ ' + response.message).removeClass('success').addClass('error');
                    }
                },
                error: function() {
                    btnSearch.prop('disabled', false).text('🔍 Search');
                    statusEl.text('❌ Failed to search. Please try again.').removeClass('success').addClass('error');
                }
            });
        }

        // Edit Row - Fetch data and populate form
        function editRow(id) {
            currentEditId = id;
            $('#btnEpicSearch').hide(); // Hide search button for Edit mode
            $('#epicSearchStatus').text('').removeClass('success error');
            $.ajax({
                url: 'crud_handler.php',
                method: 'POST',
                data: { action: 'read', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const d = response.data;
                        $('#modalTitle').text('Edit Record #' + id);
                        $('#recordId').val(d.id);
                        $('#formSno').val(d.sno);
                        $('#formEpic').val(d.epic);
                        $('#formNameTa').val(d.name_ta);
                        $('#formNameEn').val(d.name_en);
                        $('#formRelType').val(d.rel_type);
                        $('#formRelNameTa').val(d.rel_name_ta);
                        $('#formRelNameEn').val(d.rel_name_en);
                        $('#formHouseNo').val(d.house_no);
                        $('#formAge').val(d.age);
                        $('#formGender').val(d.gender);
                        $('#formBooth').val(d.booth);
                        $('#formPage').val(d.page);
                        $('#formMobile').val(d.mobile || '');
                        $('#formParty').val(d.party || '');
                        $('#formGovBen').val(d.government_beneficiaries || '');
                        $('#formPostal').val(d.postal || '');
                        openModal();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to fetch record data');
                }
            });
        }

        // Save Record (Create or Update)
        function saveRecord() {
            const form = $('#crudForm')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = $('#crudForm').serialize();
            const action = currentEditId ? 'update' : 'create';

            $.ajax({
                url: 'crud_handler.php',
                method: 'POST',
                data: formData + '&action=' + action,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        closeModal();
                        table.draw(false); // Refresh table without resetting pagination
                        fetchDashboardStats();
                        alert(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to save record');
                }
            });
        }

        // Delete Row
        function deleteRow(id) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: 'crud_handler.php',
                method: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        table.draw(false);
                        fetchDashboardStats();
                        alert(response.message);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to delete record');
                }
            });
        }

        // Close modal on outside click
        $('#crudModal').click(function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        $(document).keydown(function(e) {
            if (e.key === 'Escape' && $('#crudModal').hasClass('active')) {
                closeModal();
            }
        });
    </script>

</body>
</html>
