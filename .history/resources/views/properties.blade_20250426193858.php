@extends('layouts.app')

@section('content')
<div class="container mt-5">
    @include('components.navbar')

    <div class="row mb-4">
        <div class="col-md-12">
            <button class="btn btn-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                Show Filters
            </button>
            
            <div class="collapse mt-3" id="filterCollapse">
                <form class="form-row" >
                    <div class="form-group col-md-3">
                        <label for="radius">Radius</label>
                        <select id="radius" class="form-control">
                            <option>This location only</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="eircodeRegion">Eircode Region</label>
                        <select id="eircodeRegion" name="eircodeRegion" class="form-control" data-filter>
                            <option value="">Any Region</option>
                            <option value="A92">A92: Ardee, Louth</option>
                            <option value="Y14">Y14: Arklow, Wicklow</option>
                            <option value="A84">A84: Ashbourne, Meath</option>
                            <option value="H65">H65: Athenry, Galway</option>
                            <option value="N37">N37: Athlone, Westmeath</option>
                            <option value="R14">R14: Athy, Kildare</option>
                            <option value="K32">K32: Balbriggan, Dublin</option>
                            <option value="F26">F26: Ballina, Mayo</option>
                            <option value="H53">H53: Ballinasloe, Galway</option>
                            <option value="P31">P31: Ballincollig, Cork</option>
                            <option value="F31">F31: Ballinrobe, Mayo</option>
                            <option value="A75">A75: Ballybay, Monaghan</option>
                            <option value="A41">A41: Ballyboughal, Dublin</option>
                            <option value="F35">F35: Ballyhaunis, Mayo</option>
                            <option value="F56">F56: Ballymote, Sligo</option>
                            <option value="P72">P72: Bandon, Cork</option>
                            <option value="P75">P75: Bantry, Cork</option>
                            <option value="H14">H14: Belturbet, Cavan</option>
                            <option value="R42">R42: Birr, Offaly</option>
                            <option value="A94">A94: Blackrock, Dublin</option>
                            <option value="A94">A94: Booterstown, Dublin</option>
                            <option value="F52">F52: Boyle, Roscommon</option>
                            <option value="A98">A98: Bray, Wicklow</option>
                            <option value="V23">V23: Caherciveen, Kerry</option>
                            <option value="E21">E21: Cahir, Tipperary</option>
                            <option value="R93">R93: Carlow, Carlow</option>
                            <option value="A81">A81: Carrickmacross, Monaghan</option>
                            <option value="N41">N41: Carrick-on-Shannon, Leitrim</option>
                            <option value="E32">E32: Carrick-on-Suir, Tipperary</option>
                            <option value="P43">P43: Carrigaline, Cork</option>
                            <option value="E25">E25: Cashel, Tipperary</option>
                            <option value="F23">F23: Castlebar, Mayo</option>
                            <option value="A75">A75: Castleblaney, Monaghan</option>
                            <option value="F45">F45: Castlerea, Roscommon</option>
                            <option value="H12">H12: Cavan, Cavan</option>
                            <option value="P56">P56: Charleville, Cork</option>
                            <option value="F12">F12: Claremorris, Mayo</option>
                            <option value="H71">H71: Clifden, Galway</option>
                            <option value="P85">P85: Clonakilty, Cork</option>
                            <option value="H23">H23: Clones, Monaghan</option>
                            <option value="E91">E91: Clonmel, Tipperary</option>
                            <option value="P24">P24: Cobh, Cork</option>
                            <option value="H16">H16: Cootehill, Cavan</option>
                            <option value="T12">T12: Cork (centre and southside), Passage West, Cork Airport, Cork</option>
                            <option value="T23">T23: Cork (northside), Blarney, Cork</option>
                            <option value="P14">P14: Crookstown, Cork</option>
                            <option value="P32">P32: Donoughmore, Cork</option>
                            <option value="P47">P47: Dunmanway, Cork</option>
                            <option value="T56">T56: Watergrasshill, Cork</option>
                            <option value="T34">T34: Whitechurch, Cork</option>
                            <option value="R56">R56: Curragh Camp, Kildare</option>
                            <option value="A63">A63: Delgany, Wicklow</option>
                            <option value="F94">F94: Donegal, Donegal</option>
                            <option value="A92">A92: Drogheda, Louth</option>
                            <option value="D01">D01: Dublin 1, Dublin</option>
                            <option value="D02">D02: Dublin 2, Dublin</option>
                            <option value="D03">D03: Dublin 3, Dublin</option>
                            <option value="D04">D04: Dublin 4, Dublin</option>
                            <option value="D05">D05: Dublin 5, Dublin</option>
                            <option value="D06">D06: Dublin 6, Dublin</option>
                            <option value="D6W">D6W: Dublin 6W, Dublin</option>
                            <option value="D07">D07: Dublin 7, Dublin</option>
                            <option value="D08">D08: Dublin 8, Dublin</option>
                            <option value="D09">D09: Dublin 9, Dublin</option>
                            <option value="D10">D10: Dublin 10, Dublin</option>
                            <option value="D11">D11: Dublin 11, Dublin</option>
                            <option value="D12">D12: Dublin 12, Dublin</option>
                            <option value="D13">D13: Dublin 13, Dublin</option>
                            <option value="D14">D14: Dublin 14, Dublin</option>
                            <option value="D15">D15: Dublin 15, Dublin</option>
                            <option value="D16">D16: Dublin 16, Dublin</option>
                            <option value="D17">D17: Dublin 17, Dublin</option>
                            <option value="D18">D18: Dublin 18, Dublin</option>
                            <option value="D20">D20: Dublin 20, Dublin</option>
                            <option value="D22">D22: Dublin 22, Dublin</option>
                            <option value="D24">D24: Dublin 24, Dublin</option>
                            <option value="A86">A86: Dunboyne, Meath</option>
                            <option value="A91">A91: Dundalk, Louth</option>
                            <option value="X35">X35: Dungarvan, Waterford</option>
                            <option value="A85">A85: Dunshaughlin, Meath</option>
                            <option value="R45">R45: Edenderry, Meath</option>
                            <option value="A83">A83: Enfield, Meath</option>
                            <option value="V95">V95: Ennis, Clare</option>
                            <option value="Y21">Y21: Enniscorthy, Wexford</option>
                            <option value="P61">P61: Fermoy, Cork</option>
                            <option value="H91">H91: Galway, Galway</option>
                            <option value="A42">A42: Garristown, Dublin</option>
                            <option value="A96">A96: Glenageary, Dublin</option>
                            <option value="Y25">Y25: Gorey, Wexford</option>
                            <option value="A63">A63: Greystones, Wicklow</option>
                            <option value="A82">A82: Kells, Meath</option>
                            <option value="A63">A63: Kilcoole, Wicklow</option>
                            <option value="R51">R51: Kildare, Kildare</option>
                            <option value="R95">R95: Kilkenny, Kilkenny</option>
                            <option value="V93">V93: Killarney, Kerry</option>
                            <option value="X42">X42: Kilmacthomas, Waterford</option>
                            <option value="V35">V35: Kilmallock, Limerick</option>
                            <option value="V15">V15: Kilrush, Clare</option>
                            <option value="A82">A82: Kingscourt, Cavan</option>
                            <option value="P17">P17: Kinsale, Cork</option>
                            <option value="A92">A92: Laytown-Bettystown-Mornington, Meath</option>
                            <option value="F92">F92: Letterkenny, Donegal</option>
                            <option value="F93">F93: Lifford, Donegal</option>
                            <option value="V94">V94: Limerick, Limerick</option>
                            <option value="V31">V31: Listowel, Kerry</option>
                            <option value="T45">T45: Little Island, Cork</option>
                            <option value="N39">N39: Longford, Longford</option>
                            <option value="H62">H62: Loughrea, Galway</option>
                            <option value="K78">K78: Lucan, Dublin</option>
                            <option value="K45">K45: Lusk, Dublin</option>
                            <option value="P12">P12: Macroom, Cork</option>
                            <option value="K36">K36: Malahide, Dublin</option>
                            <option value="P51">P51: Mallow, Cork</option>
                            <option value="W23">W23: Maynooth, Kildare</option>
                            <option value="P25">P25: Midleton, Cork</option>
                            <option value="P67">P67: Mitchelstown, Cork</option>
                            <option value="H18">H18: Monaghan, Monaghan</option>
                            <option value="W34">W34: Monasterevin, Kildare</option>
                            <option value="A94">A94: Monkstown, Dublin</option>
                            <option value="R21">R21: Muine Bheag, Carlow</option>
                            <option value="N91">N91: Mullingar, Westmeath</option>
                            <option value="W91">W91: Naas, Kildare</option>
                            <option value="C15">C15: Navan, Meath</option>
                            <option value="E45">E45: Nenagh, Tipperary</option>
                            <option value="Y34">Y34: New Ross, Wexford</option>
                            <option value="W12">W12: Newbridge, Kildare</option>
                            <option value="A63">A63: Newcastle, Wicklow</option>
                            <option value="V42">V42: Newcastle West, Limerick</option>
                            <option value="A63">A63: Newtownmountkennedy, Wicklow</option>
                            <option value="A45">A45: Oldtown, Dublin</option>
                            <option value="R32">R32: Portlaoise, Laois</option>
                            <option value="A67">A67: Rathnew, Wicklow</option>
                            <option value="A85">A85: Ratoath, Meath</option>
                            <option value="F42">F42: Roscommon, Roscommon</option>
                            <option value="E53">E53: Roscrea, Tipperary</option>
                            <option value="K56">K56: Rush, Dublin</option>
                            <option value="V14">V14: Shannon, Clare</option>
                            <option value="K34">K34: Skerries, Dublin</option>
                            <option value="P81">P81: Skibbereen, Cork</option>
                            <option value="F91">F91: Sligo, Sligo</option>
                            <option value="A83">A83: Summerhill, Meath</option>
                            <option value="K67">K67: Swords and Dublin Airport, Dublin</option>
                            <option value="E41">E41: Thurles, Tipperary</option>
                            <option value="E34">E34: Tipperary, Tipperary</option>
                            <option value="V92">V92: Tralee, Kerry</option>
                            <option value="H54">H54: Tuam, Galway</option>
                            <option value="R35">R35: Tullamore, Offaly</option>
                            <option value="A82">A82: Virginia, Cavan</option>
                            <option value="X91">X91: Waterford, Waterford</option>
                            <option value="F28">F28: Westport, Mayo</option>
                            <option value="Y35">Y35: Wexford, Wexford</option>
                            <option value="A67">A67: Wicklow, Wicklow</option>
                            <option value="P36">P36: Youghal, Cork</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="priceMin">Price Min</label>
                        <input type="number" id="priceMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="priceMax">Price Max</label>
                        <input type="number" id="priceMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bedsMin">Beds Min</label>
                        <input type="number" id="bedsMin" class="form-control">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="bedsMax">Beds Max</label>
                        <input type="number" id="bedsMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="type">Type</label>
                        <select id="type" class="form-control">
                            <option>Any Property</option>
                            <option>House</option>
                            <option>Detached House</option>
                            <option>Semi-Detached House</option>
                            <option>Terraced House</option>
                            <option>End of Terrace House</option>
                            <option>Townhouse</option>
                            <option>Apartment</option>
                            <option>Studio Apartment</option>
                            <option>Duplex</option>
                            <option>Bungalow</option>
                            <option>Site</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bathsMin">Baths Min</label>
                        <input type="number" id="bathsMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bathsMax">Baths Max</label>
                        <input type="number" id="bathsMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="facilities">Facilities</label>
                        <select id="facilities" class="form-control">
                            <option>Any Facilities</option>
                            <option>Alarm</option>
                            <option>Gas Fired Central Heating</option>
                            <option>Oil Fired Central Heating</option>
                            <option>Parking</option>
                            <option>Wheelchair Access</option>
                            <option>Wired for Cable TV</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sizeMin">Size Min (m²)</label>
                        <input type="number" id="sizeMin" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sizeMax">Size Max (m²)</label>
                        <input type="number" id="sizeMax" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="berRating">BER Rating</label>
                        <select id="berRating" class="form-control">
                            <option>Any</option>
                            <!-- Add more BER rating options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="saleType">Sale Type</label>
                        <select id="saleType" class="form-control">
                            <option>All Sales</option>
                            <option>Online Offers Only</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="mediaType">Media Type</label>
                        <select id="mediaType" class="form-control">
                            <option>Any Media</option>
                            <option>Ads with video</option>
                            <option>Ads with virtual tours</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="keyword">Keyword / Address Search</label>
                        <input type="text" id="keyword" class="form-control" placeholder='e.g. garage or "period property"'>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="availability">Availability</label>
                        <select id="availability" class="form-control">
                            <option>Available</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="addedInLast">Added In Last</label>
                        <select id="addedInLast" class="form-control">
                            <option>At any time</option>
                            <!-- Add more options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="sort">Sort</label>
                        <select id="sort" class="form-control">
                            <option>Best Match</option>
                            <!-- Add more sort options here -->
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <button type="Submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @php
        if (!isset($properties) || count($properties) == 0) {
            echo '<div class="alert alert-warning" role="alert">No properties found.</div>';
        }
    @endphp
    @foreach ($properties as $property)
    <div class="card mb-3">
        <div class="row no-gutters">
            <!-- Property Image -->
            <div class="col-md-4">
                @php
       
                    $imagePath = (isset($property->cover_image) ) ? $property->cover_image : '';
                @endphp
                <img src="{{ asset('storage/' . $imagePath) }}" class="card-img" alt="{{ $property->name }}">
            </div>

            <!-- Property Details -->
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="{{ url('/properties/' . $property->id) }}">{{ $property->name }}</a>
                    </h5>
                    <p class="card-text">€{{ number_format($property->price, 2) }}</p>
                    <p class="card-text">
                        {{ $property->beds }} Bed • {{ $property->baths }} Bath • {{ $property->size }} m² • {{ $property->type }}
                    </p>
                    <p class="card-text">BER: {{ $property->ber_rating }}</p>
                    <p class="card-text">{{ $property->address }}</p>
                    <p class="card-text text-truncate" style="max-width: 100%;">
                        {{ $property->description }}
                    </p>
                </div>
            </div>
        </div>
    </div>

@endforeach
@endsection
