<?php
/**
 * Rental Object Type Schemas
 * Definerer feltskjemaer for alle 12 utleieobjekttyper
 */

function getRentalSchemas() {
    return [
        'residence' => [
            'label' => 'Boenhet (Leilighet/Hus)',
            'table' => 'rental_residences',
            'fields' => [
                'sqm' => ['type' => 'number', 'label' => 'Kvadratmeter (m²)', 'required' => false],
                'bedrooms' => ['type' => 'number', 'label' => 'Antall soverom', 'required' => false],
                'bathrooms' => ['type' => 'number', 'label' => 'Antall bad', 'required' => false],
                'floor' => ['type' => 'number', 'label' => 'Etasje', 'required' => false],
                'elevator' => ['type' => 'checkbox', 'label' => 'Heis', 'required' => false],
                'parking_spaces' => ['type' => 'number', 'label' => 'Antall parkeringsplasser', 'required' => false],
                'balcony' => ['type' => 'checkbox', 'label' => 'Balkong', 'required' => false],
                'furnished' => ['type' => 'select', 'label' => 'Møblering', 'options' => ['Møblert', 'Umøblert', 'Delvis møblert'], 'required' => false],
                'lease_type' => ['type' => 'select', 'label' => 'Leietype', 'options' => ['Langtidsleie', 'Korttidsleie', 'Begge'], 'required' => false],
                'min_lease_months' => ['type' => 'number', 'label' => 'Minimum leieperiode (måneder)', 'required' => false],
                'pets_allowed' => ['type' => 'checkbox', 'label' => 'Kjæledyr tillatt', 'required' => false],
                'smoking_allowed' => ['type' => 'checkbox', 'label' => 'Røyking tillatt', 'required' => false],
                'energy_rating' => ['type' => 'text', 'label' => 'Energimerking', 'required' => false],
                'internet_included' => ['type' => 'checkbox', 'label' => 'Internett inkludert', 'required' => false],
                'heating_type' => ['type' => 'text', 'label' => 'Oppvarmingstype', 'required' => false],
                'year_built' => ['type' => 'number', 'label' => 'Byggeår', 'required' => false],
                'last_renovated' => ['type' => 'number', 'label' => 'Sist renovert (år)', 'required' => false]
            ]
        ],
        
        'cabin' => [
            'label' => 'Hytte/Feriehus',
            'table' => 'rental_cabins',
            'fields' => [
                'sqm' => ['type' => 'number', 'label' => 'Kvadratmeter (m²)', 'required' => false],
                'bedrooms' => ['type' => 'number', 'label' => 'Antall soverom', 'required' => false],
                'beds' => ['type' => 'number', 'label' => 'Antall sengeplasser', 'required' => false],
                'bathrooms' => ['type' => 'number', 'label' => 'Antall bad', 'required' => false],
                'season_type' => ['type' => 'select', 'label' => 'Sesongtype', 'options' => ['Helår', 'Vinter', 'Sommer'], 'required' => false],
                'min_nights' => ['type' => 'number', 'label' => 'Minimum antall netter', 'required' => false],
                'cleaning_fee' => ['type' => 'number', 'label' => 'Rengjøringsavgift (kr)', 'step' => '0.01', 'required' => false],
                'distance_to_ski' => ['type' => 'text', 'label' => 'Avstand til skiløyper', 'required' => false],
                'distance_to_store' => ['type' => 'text', 'label' => 'Avstand til butikk', 'required' => false],
                'water_supply' => ['type' => 'text', 'label' => 'Vannforsyning', 'required' => false],
                'sewage' => ['type' => 'text', 'label' => 'Avløp', 'required' => false],
                'heating' => ['type' => 'text', 'label' => 'Oppvarming', 'required' => false],
                'facilities' => ['type' => 'textarea', 'label' => 'Fasiliteter (kommaseparert)', 'required' => false],
                'max_guests' => ['type' => 'number', 'label' => 'Maks antall gjester', 'required' => false],
                'pet_fee' => ['type' => 'number', 'label' => 'Avgift for kjæledyr (kr)', 'step' => '0.01', 'required' => false],
                'linen_included' => ['type' => 'checkbox', 'label' => 'Sengetøy inkludert', 'required' => false]
            ]
        ],
        
        'venue' => [
            'label' => 'Selskapslokale/Konferanserom',
            'table' => 'rental_venues',
            'fields' => [
                'sqm' => ['type' => 'number', 'label' => 'Kvadratmeter (m²)', 'required' => false],
                'capacity_seated' => ['type' => 'number', 'label' => 'Kapasitet sittende', 'required' => false],
                'capacity_standing' => ['type' => 'number', 'label' => 'Kapasitet stående', 'required' => false],
                'kitchen_type' => ['type' => 'select', 'label' => 'Kjøkken', 'options' => ['Ingen', 'Tekjøkken', 'Fullstendig'], 'required' => false],
                'video_equipment' => ['type' => 'textarea', 'label' => 'Videoutstyr', 'required' => false],
                'sound_systems' => ['type' => 'textarea', 'label' => 'Lydanlegg', 'required' => false],
                'tables_chairs_included' => ['type' => 'checkbox', 'label' => 'Bord og stoler inkludert', 'required' => false],
                'parking_spaces' => ['type' => 'number', 'label' => 'Antall parkeringsplasser', 'required' => false],
                'min_days' => ['type' => 'number', 'label' => 'Minimum antall dager', 'required' => false],
                'cleaning_included' => ['type' => 'checkbox', 'label' => 'Rengjøring inkludert', 'required' => false],
                'cleaning_fee' => ['type' => 'number', 'label' => 'Rengjøringsavgift (kr)', 'step' => '0.01', 'required' => false],
                 'facilities' => ['type' => 'textarea', 'label' => 'Fasiliteter (kommaseparert)', 'required' => false],
                 'access_code' => ['type' => 'text', 'label' => 'Tilgangskode', 'maxlength' => 6, 'required' => false, 'placeholder' => '1–6 bokstaver eller tall']
            ]
        ],
        
        'container' => [
            'label' => 'Container/Lagring',
            'table' => 'rental_containers',
            'fields' => [
                'container_type' => ['type' => 'select', 'label' => 'Containertype', 'options' => ['Åpen', 'Lukket', 'Kombi'], 'required' => false],
                'cubic_meters' => ['type' => 'number', 'label' => 'Kubikkmeter', 'step' => '0.01', 'required' => false],
                'sqm_floor' => ['type' => 'number', 'label' => 'Gulvareal (m²)', 'step' => '0.01', 'required' => false],
                'length_m' => ['type' => 'number', 'label' => 'Lengde (m)', 'step' => '0.01', 'required' => false],
                'width_m' => ['type' => 'number', 'label' => 'Bredde (m)', 'step' => '0.01', 'required' => false],
                'height_m' => ['type' => 'number', 'label' => 'Høyde (m)', 'step' => '0.01', 'required' => false],
                'container_condition' => ['type' => 'select', 'label' => 'Stand', 'options' => ['Ny', 'God', 'Slitt'], 'required' => false],
                'weatherproof' => ['type' => 'checkbox', 'label' => 'Værbestandig', 'required' => false],
                'lockable' => ['type' => 'checkbox', 'label' => 'Låsbar', 'required' => false],
                'delivery_available' => ['type' => 'checkbox', 'label' => 'Levering tilgjengelig', 'required' => false],
                'delivery_cost' => ['type' => 'number', 'label' => 'Leveringskostnad (kr)', 'step' => '0.01', 'required' => false],
                'access_type' => ['type' => 'text', 'label' => 'Tilgangstype', 'required' => false],
                'climate_controlled' => ['type' => 'checkbox', 'label' => 'Klimakontrollert', 'required' => false],
                'shelving_included' => ['type' => 'checkbox', 'label' => 'Hyller inkludert', 'required' => false]
            ]
        ],
        
        'tool' => [
            'label' => 'Verktøy/Utstyr',
            'table' => 'rental_tools',
            'fields' => [
                'brand' => ['type' => 'text', 'label' => 'Merke', 'required' => false],
                'model' => ['type' => 'text', 'label' => 'Modell', 'required' => false],
                'year' => ['type' => 'number', 'label' => 'Årsmodell', 'required' => false],
                'tool_condition' => ['type' => 'select', 'label' => 'Stand', 'options' => ['Ny', 'Som ny', 'God', 'Slitt'], 'required' => false],
                'power_type' => ['type' => 'text', 'label' => 'Strømtype', 'required' => false],
                'weight_kg' => ['type' => 'number', 'label' => 'Vekt (kg)', 'step' => '0.01', 'required' => false],
                'included_accessories' => ['type' => 'textarea', 'label' => 'Inkludert tilbehør', 'required' => false],
                'requires_training' => ['type' => 'checkbox', 'label' => 'Krever opplæring', 'required' => false],
                'insurance_required' => ['type' => 'checkbox', 'label' => 'Forsikring påkrevd', 'required' => false],
                'fuel_type' => ['type' => 'text', 'label' => 'Drivstofftype', 'required' => false],
                'maintenance_status' => ['type' => 'text', 'label' => 'Vedlikeholdsstatus', 'required' => false],
                'safety_equipment_included' => ['type' => 'checkbox', 'label' => 'Sikkerhetsutstyr inkludert', 'required' => false],
                'manual_url' => ['type' => 'text', 'label' => 'Lenke til manual', 'required' => false]
            ]
        ],
        
        'vehicle' => [
            'label' => 'Kjøretøy (Bil/Varebil)',
            'table' => 'rental_vehicles',
            'fields' => [
                'make' => ['type' => 'text', 'label' => 'Merke', 'required' => false],
                'model' => ['type' => 'text', 'label' => 'Modell', 'required' => false],
                'year' => ['type' => 'number', 'label' => 'Årsmodell', 'required' => false],
                'license_plate' => ['type' => 'text', 'label' => 'Reg.nr.', 'required' => false],
                'fuel_type' => ['type' => 'select', 'label' => 'Drivstoff', 'options' => ['Bensin', 'Diesel', 'Elektrisk', 'Hybrid'], 'required' => false],
                'transmission' => ['type' => 'select', 'label' => 'Girkasse', 'options' => ['Manuell', 'Automat'], 'required' => false],
                'seats' => ['type' => 'number', 'label' => 'Antall seter', 'required' => false],
                'cargo_space_m3' => ['type' => 'number', 'label' => 'Lasterom (m³)', 'step' => '0.01', 'required' => false],
                'towing_capacity_kg' => ['type' => 'number', 'label' => 'Tilhengervekt (kg)', 'required' => false],
                'mileage_km' => ['type' => 'number', 'label' => 'Kilometerstand', 'required' => false],
                'fuel_policy' => ['type' => 'text', 'label' => 'Drivstoffpolicy', 'required' => false],
                'insurance_included' => ['type' => 'checkbox', 'label' => 'Forsikring inkludert', 'required' => false],
                'km_included_per_day' => ['type' => 'number', 'label' => 'Inkluderte km per dag', 'required' => false],
                'extra_km_cost' => ['type' => 'number', 'label' => 'Pris per ekstra km', 'step' => '0.01', 'required' => false],
                'min_driver_age' => ['type' => 'number', 'label' => 'Min føreralder', 'required' => false],
                'driver_license_class' => ['type' => 'text', 'label' => 'Førerkortklasse', 'required' => false],
                'license_years_required' => ['type' => 'number', 'label' => 'År med førerkort påkrevd', 'required' => false],
                'additional_certificates' => ['type' => 'textarea', 'label' => 'Ekstra sertifikater', 'required' => false],
                'winter_tires' => ['type' => 'checkbox', 'label' => 'Vinterdekk', 'required' => false],
                'equipment' => ['type' => 'textarea', 'label' => 'Utstyr (kommaseparert)', 'required' => false]
            ]
        ],
        
        'office' => [
            'label' => 'Kontor/Næringsbygg',
            'table' => 'rental_offices',
            'fields' => [
                'sqm' => ['type' => 'number', 'label' => 'Kvadratmeter (m²)', 'required' => false],
                'office_rooms' => ['type' => 'number', 'label' => 'Antall kontorrom', 'required' => false],
                'meeting_rooms' => ['type' => 'number', 'label' => 'Antall møterom', 'required' => false],
                'reception_area' => ['type' => 'checkbox', 'label' => 'Resepsjonsområde', 'required' => false],
                'floor' => ['type' => 'number', 'label' => 'Etasje', 'required' => false],
                'elevator' => ['type' => 'checkbox', 'label' => 'Heis', 'required' => false],
                'parking_spaces' => ['type' => 'number', 'label' => 'Antall parkeringsplasser', 'required' => false],
                'kitchenette' => ['type' => 'checkbox', 'label' => 'Tekjøkken', 'required' => false],
                'internet_speed_mbps' => ['type' => 'number', 'label' => 'Internett hastighet (Mbps)', 'required' => false],
                'air_conditioning' => ['type' => 'checkbox', 'label' => 'Aircondition', 'required' => false],
                'security_system' => ['type' => 'checkbox', 'label' => 'Sikkerhetssystem', 'required' => false],
                'accessibility' => ['type' => 'text', 'label' => 'Tilgjengelighet', 'required' => false],
                'lease_type' => ['type' => 'select', 'label' => 'Leietype', 'options' => ['Langtidsleie', 'Korttidsleie'], 'required' => false],
                'min_lease_months' => ['type' => 'number', 'label' => 'Minimum leieperiode (måneder)', 'required' => false],
                'energy_rating' => ['type' => 'text', 'label' => 'Energimerking', 'required' => false],
                'operating_costs_included' => ['type' => 'checkbox', 'label' => 'Felleskostnader inkludert', 'required' => false],
                'facilities' => ['type' => 'textarea', 'label' => 'Fasiliteter (kommaseparert)', 'required' => false]
            ]
        ],
        
        'sports_equipment' => [
            'label' => 'Sportsutstyr',
            'table' => 'rental_sports_equipment',
            'fields' => [
                'sport_type' => ['type' => 'text', 'label' => 'Idrettstype', 'required' => false],
                'equipment_type' => ['type' => 'text', 'label' => 'Utstyrstype', 'required' => false],
                'brand' => ['type' => 'text', 'label' => 'Merke', 'required' => false],
                'model' => ['type' => 'text', 'label' => 'Modell', 'required' => false],
                'size' => ['type' => 'text', 'label' => 'Størrelse', 'required' => false],
                'skill_level' => ['type' => 'select', 'label' => 'Ferdighetsnivå', 'options' => ['Nybegynner', 'Viderekommen', 'Ekspert'], 'required' => false],
                'equipment_condition' => ['type' => 'select', 'label' => 'Stand', 'options' => ['Ny', 'God', 'Slitt'], 'required' => false],
                'year' => ['type' => 'number', 'label' => 'Årsmodell', 'required' => false],
                'includes' => ['type' => 'textarea', 'label' => 'Inkluderer (kommaseparert)', 'required' => false],
                'boots_available' => ['type' => 'checkbox', 'label' => 'Sko tilgjengelig', 'required' => false],
                'helmet_available' => ['type' => 'checkbox', 'label' => 'Hjelm tilgjengelig', 'required' => false],
                'insurance_recommended' => ['type' => 'checkbox', 'label' => 'Forsikring anbefalt', 'required' => false],
                'age_range' => ['type' => 'text', 'label' => 'Aldersgruppe', 'required' => false]
            ]
        ],
        
        'parking' => [
            'label' => 'Parkering/Garasje',
            'table' => 'rental_parking',
            'fields' => [
                'parking_type' => ['type' => 'select', 'label' => 'Parkeringstype', 'options' => ['Utendørs', 'Innendørs garasje', 'Carport'], 'required' => false],
                'sqm' => ['type' => 'number', 'label' => 'Areal (m²)', 'required' => false],
                'height_clearance_m' => ['type' => 'number', 'label' => 'Takhøyde (m)', 'step' => '0.01', 'required' => false],
                'vehicle_types' => ['type' => 'textarea', 'label' => 'Kjøretøytyper (kommaseparert)', 'required' => false],
                'electric_charging' => ['type' => 'checkbox', 'label' => 'Elbillading', 'required' => false],
                'charging_type' => ['type' => 'text', 'label' => 'Ladetype', 'required' => false],
                'security' => ['type' => 'text', 'label' => 'Sikkerhet', 'required' => false],
                'access_247' => ['type' => 'checkbox', 'label' => '24/7 tilgang', 'required' => false],
                'covered' => ['type' => 'checkbox', 'label' => 'Overdekket', 'required' => false],
                'heated' => ['type' => 'checkbox', 'label' => 'Oppvarmet', 'required' => false],
                'distance_to_address' => ['type' => 'text', 'label' => 'Avstand til adresse', 'required' => false]
            ]
        ],
        
        'rv' => [
            'label' => 'Campingvogn/Bobil',
            'table' => 'rental_rvs',
            'fields' => [
                'vehicle_type' => ['type' => 'select', 'label' => 'Kjøretøytype', 'options' => ['Bobil', 'Campingvogn', 'Leiebil med henger'], 'required' => false],
                'make' => ['type' => 'text', 'label' => 'Merke', 'required' => false],
                'model' => ['type' => 'text', 'label' => 'Modell', 'required' => false],
                'year' => ['type' => 'number', 'label' => 'Årsmodell', 'required' => false],
                'license_plate' => ['type' => 'text', 'label' => 'Reg.nr.', 'required' => false],
                'length_m' => ['type' => 'number', 'label' => 'Lengde (m)', 'step' => '0.01', 'required' => false],
                'sleeping_capacity' => ['type' => 'number', 'label' => 'Antall soveplasser', 'required' => false],
                'fixed_beds' => ['type' => 'number', 'label' => 'Antall faste senger', 'required' => false],
                'toilet' => ['type' => 'text', 'label' => 'Toalett', 'required' => false],
                'shower' => ['type' => 'checkbox', 'label' => 'Dusj', 'required' => false],
                'kitchen' => ['type' => 'select', 'label' => 'Kjøkken', 'options' => ['Ingen', 'Enkel', 'Fullstendig'], 'required' => false],
                'heating' => ['type' => 'text', 'label' => 'Oppvarming', 'required' => false],
                'air_conditioning' => ['type' => 'checkbox', 'label' => 'Aircondition', 'required' => false],
                'awning' => ['type' => 'checkbox', 'label' => 'Markise', 'required' => false],
                'bike_rack' => ['type' => 'checkbox', 'label' => 'Sykkelholder', 'required' => false],
                'solar_panels' => ['type' => 'checkbox', 'label' => 'Solcellepanel', 'required' => false],
                'km_included_per_day' => ['type' => 'number', 'label' => 'Inkluderte km per dag', 'required' => false],
                'fuel_type' => ['type' => 'text', 'label' => 'Drivstoff', 'required' => false],
                'transmission' => ['type' => 'select', 'label' => 'Girkasse', 'options' => ['Manuell', 'Automat'], 'required' => false],
                'min_driver_age' => ['type' => 'number', 'label' => 'Min føreralder', 'required' => false],
                'driver_license_required' => ['type' => 'text', 'label' => 'Førerkortklasse', 'required' => false],
                'license_years_required' => ['type' => 'number', 'label' => 'År med førerkort påkrevd', 'required' => false],
                'additional_certificates' => ['type' => 'textarea', 'label' => 'Ekstra sertifikater', 'required' => false]
            ]
        ],
        
        'event_item' => [
            'label' => 'Eventartikler (Telt/Stoler/Bord)',
            'table' => 'rental_event_items',
            'fields' => [
                'item_type' => ['type' => 'text', 'label' => 'Varetype', 'required' => false],
                'dimensions' => ['type' => 'text', 'label' => 'Dimensjoner', 'required' => false],
                'capacity' => ['type' => 'number', 'label' => 'Kapasitet', 'required' => false],
                'color' => ['type' => 'text', 'label' => 'Farge', 'required' => false],
                'material' => ['type' => 'text', 'label' => 'Materiale', 'required' => false],
                'weatherproof' => ['type' => 'checkbox', 'label' => 'Værbestandig', 'required' => false],
                'sides_included' => ['type' => 'number', 'label' => 'Antall sidevegger', 'required' => false],
                'windows' => ['type' => 'checkbox', 'label' => 'Vinduer', 'required' => false],
                'setup_included' => ['type' => 'checkbox', 'label' => 'Oppsett inkludert', 'required' => false],
                'setup_cost' => ['type' => 'number', 'label' => 'Kostnad oppsett (kr)', 'step' => '0.01', 'required' => false],
                'takedown_included' => ['type' => 'checkbox', 'label' => 'Nedrigging inkludert', 'required' => false],
                'flooring_available' => ['type' => 'checkbox', 'label' => 'Gulv tilgjengelig', 'required' => false],
                'lighting_available' => ['type' => 'checkbox', 'label' => 'Belysning tilgjengelig', 'required' => false],
                'heating_available' => ['type' => 'checkbox', 'label' => 'Oppvarming tilgjengelig', 'required' => false],
                'min_rental_days' => ['type' => 'number', 'label' => 'Minimum leiedager', 'required' => false]
            ]
        ],
        
        'trailer' => [
            'label' => 'Tilhenger/Skaphenger',
            'table' => 'rental_trailers',
            'fields' => [
                'trailer_type' => ['type' => 'select', 'label' => 'Tilhengertype', 'options' => ['Åpen henger', 'Skaphenger', 'Båthenger', 'Maskinhenger', 'Tilhenger med presenning'], 'required' => false],
                'load_capacity_kg' => ['type' => 'number', 'label' => 'Lastekapasitet (kg)', 'required' => false],
                'length_m' => ['type' => 'number', 'label' => 'Lengde (m)', 'step' => '0.01', 'required' => false],
                'width_m' => ['type' => 'number', 'label' => 'Bredde (m)', 'step' => '0.01', 'required' => false],
                'height_m' => ['type' => 'number', 'label' => 'Høyde (m)', 'step' => '0.01', 'required' => false],
                'coupling_type' => ['type' => 'text', 'label' => 'Koblingstype', 'required' => false],
                'brakes' => ['type' => 'checkbox', 'label' => 'Bremser', 'required' => false],
                'license_plate' => ['type' => 'text', 'label' => 'Reg.nr.', 'required' => false],
                'year' => ['type' => 'number', 'label' => 'Årsmodell', 'required' => false],
                'ramp_available' => ['type' => 'checkbox', 'label' => 'Rampe tilgjengelig', 'required' => false],
                'lashing_points' => ['type' => 'number', 'label' => 'Antall surringspunkter', 'required' => false],
                'cover_included' => ['type' => 'checkbox', 'label' => 'Presenning inkludert', 'required' => false],
                'sides_foldable' => ['type' => 'checkbox', 'label' => 'Foldbare sider', 'required' => false],
                'registration_required' => ['type' => 'checkbox', 'label' => 'Registrering påkrevd', 'required' => false],
                'trailer_condition' => ['type' => 'select', 'label' => 'Stand', 'options' => ['Ny', 'God', 'Slitt'], 'required' => false],
                'driver_license_class' => ['type' => 'text', 'label' => 'Førerkortklasse', 'required' => false],
                'license_years_required' => ['type' => 'number', 'label' => 'År med førerkort påkrevd', 'required' => false],
                'min_driver_age' => ['type' => 'number', 'label' => 'Min føreralder', 'required' => false]
            ]
        ]
    ];
}

/**
 * Hent skjema for spesifikk objekttype
 */
function getRentalSchema($type) {
    $schemas = getRentalSchemas();
    return $schemas[$type] ?? null;
}

/**
 * Valider data mot skjema
 */
function validateRentalData($type, $data) {
    $schema = getRentalSchema($type);
    if (!$schema) {
        return ['valid' => false, 'error' => 'Ugyldig objekttype'];
    }
    
    foreach ($schema['fields'] as $field => $config) {
        if ($config['required'] && empty($data[$field])) {
            return ['valid' => false, 'error' => "{$config['label']} er påkrevd"];
        }
    }
    
    return ['valid' => true];
}
