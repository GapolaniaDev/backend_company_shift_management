<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Location;

class LocationsSeeder extends Seeder
{
    private $adelaideLocations = [
        // CBD and Inner City
        ['name' => 'Adelaide CBD Office Tower', 'address' => '1 King William Street, Adelaide SA 5000', 'lat' => -34.9286, 'lng' => 138.6007],
        ['name' => 'Rundle Mall Shopping Complex', 'address' => 'Rundle Mall, Adelaide SA 5000', 'lat' => -34.9215, 'lng' => 138.6010],
        ['name' => 'North Terrace Medical Centre', 'address' => '123 North Terrace, Adelaide SA 5000', 'lat' => -34.9197, 'lng' => 138.5986],
        ['name' => 'Hindley Street Commercial Building', 'address' => '45 Hindley Street, Adelaide SA 5000', 'lat' => -34.9266, 'lng' => 138.5947],
        ['name' => 'Grenfell Street Professional Complex', 'address' => '78 Grenfell Street, Adelaide SA 5000', 'lat' => -34.9242, 'lng' => 138.6089],
        
        // North Adelaide
        ['name' => 'North Adelaide Business Centre', 'address' => '156 Melbourne Street, North Adelaide SA 5006', 'lat' => -34.9089, 'lng' => 138.5986],
        ['name' => 'O\'Connell Street Medical Plaza', 'address' => '234 O\'Connell Street, North Adelaide SA 5006', 'lat' => -34.9067, 'lng' => 138.5956],
        ['name' => 'Prospect Road Corporate Hub', 'address' => '89 Prospect Road, Prospect SA 5082', 'lat' => -34.8856, 'lng' => 138.5947],
        
        // Eastern Suburbs
        ['name' => 'Burnside Village Shopping Centre', 'address' => '447 Portrush Road, Glenside SA 5065', 'lat' => -34.9456, 'lng' => 138.6334],
        ['name' => 'Norwood Parade Office Complex', 'address' => '167 The Parade, Norwood SA 5067', 'lat' => -34.9189, 'lng' => 138.6278],
        ['name' => 'Magill Road Medical Centre', 'address' => '345 Magill Road, St Morris SA 5068', 'lat' => -34.9089, 'lng' => 138.6456],
        ['name' => 'Glynburn Road Professional Building', 'address' => '123 Glynburn Road, Glynde SA 5070', 'lat' => -34.8867, 'lng' => 138.6578],
        
        // Western Suburbs
        ['name' => 'Henley Beach Business Park', 'address' => '78 Seaview Road, Henley Beach SA 5022', 'lat' => -34.9156, 'lng' => 138.4967],
        ['name' => 'Grange Road Shopping Complex', 'address' => '234 Grange Road, Findon SA 5023', 'lat' => -34.8989, 'lng' => 138.5234],
        ['name' => 'West Lakes Shopping Centre', 'address' => '111 West Lakes Boulevard, West Lakes SA 5021', 'lat' => -34.8767, 'lng' => 138.4956],
        ['name' => 'Port Adelaide Commercial Centre', 'address' => '56 Commercial Road, Port Adelaide SA 5015', 'lat' => -34.8456, 'lng' => 138.5067],
        
        // Southern Suburbs
        ['name' => 'Marion Shopping Centre Office', 'address' => '297 Diagonal Road, Oaklands Park SA 5046', 'lat' => -35.0089, 'lng' => 138.5456],
        ['name' => 'Brighton Road Medical Complex', 'address' => '445 Brighton Road, Brighton SA 5048', 'lat' => -35.0234, 'lng' => 138.5234],
        ['name' => 'Flinders Medical Centre Facility', 'address' => '1 Flinders Drive, Bedford Park SA 5042', 'lat' => -35.0345, 'lng' => 138.5678],
        ['name' => 'Reynella Shopping Centre', 'address' => '100 Reynell Road, Reynella SA 5161', 'lat' => -35.0956, 'lng' => 138.5234],
        
        // Hills and Eastern Adelaide
        ['name' => 'Stirling Hospital Complex', 'address' => '123 Mount Barker Road, Stirling SA 5152', 'lat' => -35.0089, 'lng' => 138.7234],
        ['name' => 'Mount Barker Business Centre', 'address' => '67 Adelaide Road, Mount Barker SA 5251', 'lat' => -35.0678, 'lng' => 138.8567],
        ['name' => 'Crafers Medical Centre', 'address' => '234 Adelaide Hills Road, Crafers SA 5152', 'lat' => -34.9789, 'lng' => 138.7456],
        
        // Northern Suburbs  
        ['name' => 'Salisbury Shopping Centre Office', 'address' => '50 Church Street, Salisbury SA 5108', 'lat' => -34.7567, 'lng' => 138.6423],
        ['name' => 'Elizabeth Shopping Centre', 'address' => '50 Elizabeth Way, Elizabeth SA 5112', 'lat' => -34.7156, 'lng' => 138.6678],
        ['name' => 'Parafield Gardens Medical Plaza', 'address' => '123 Salisbury Highway, Parafield Gardens SA 5107', 'lat' => -34.7789, 'lng' => 138.6234],
        ['name' => 'Munno Para Shopping Complex', 'address' => '651 Curtis Road, Munno Para SA 5115', 'lat' => -34.6789, 'lng' => 138.7123],
        
        // Additional locations to reach 60 total (15 per company)
        ['name' => 'Gepps Cross Industrial Centre', 'address' => '45 Grand Junction Road, Gepps Cross SA 5094', 'lat' => -34.8234, 'lng' => 138.6456],
        ['name' => 'Mawson Lakes Technology Park', 'address' => '78 Technology Circuit, Mawson Lakes SA 5095', 'lat' => -34.8089, 'lng' => 138.6123],
        ['name' => 'Golden Grove Village Centre', 'address' => '123 The Golden Way, Golden Grove SA 5125', 'lat' => -34.7890, 'lng' => 138.7234],
        ['name' => 'Tea Tree Plaza Office Complex', 'address' => '976 North East Road, Modbury SA 5092', 'lat' => -34.8345, 'lng' => 138.6789],
        
        // Inner South
        ['name' => 'Unley Road Business Centre', 'address' => '234 Unley Road, Unley SA 5061', 'lat' => -34.9456, 'lng' => 138.6012],
        ['name' => 'Goodwood Road Medical Hub', 'address' => '156 Goodwood Road, Goodwood SA 5034', 'lat' => -34.9567, 'lng' => 138.5834],
        ['name' => 'Kensington Park Office Tower', 'address' => '89 The Parade, Kensington Park SA 5068', 'lat' => -34.9234, 'lng' => 138.6345],
        
        // Outer Metro
        ['name' => 'Gawler Main Street Complex', 'address' => '45 Murray Street, Gawler SA 5118', 'lat' => -34.6012, 'lng' => 138.7456],
        ['name' => 'Victor Harbor Medical Centre', 'address' => '123 Ocean Street, Victor Harbor SA 5211', 'lat' => -35.5567, 'lng' => 138.6234],
        ['name' => 'Mount Pleasant Business Park', 'address' => '67 Adelaide Road, Mount Pleasant SA 5235', 'lat' => -34.7789, 'lng' => 139.0456],
        
        // Additional CBD and Inner locations
        ['name' => 'Currie Street Professional Hub', 'address' => '234 Currie Street, Adelaide SA 5000', 'lat' => -34.9245, 'lng' => 138.5923],
        ['name' => 'Pirie Street Medical Complex', 'address' => '145 Pirie Street, Adelaide SA 5000', 'lat' => -34.9267, 'lng' => 138.6045],
        ['name' => 'Franklin Street Office Tower', 'address' => '89 Franklin Street, Adelaide SA 5000', 'lat' => -34.9289, 'lng' => 138.6078],
        ['name' => 'Morphett Street Business Centre', 'address' => '167 Morphett Street, Adelaide SA 5000', 'lat' => -34.9334, 'lng' => 138.5956],
        
        // Mid-Metro locations
        ['name' => 'Woodville West Shopping Complex', 'address' => '234 Woodville Road, Woodville West SA 5011', 'lat' => -34.8789, 'lng' => 138.5456],
        ['name' => 'Seaton Park Medical Centre', 'address' => '123 Tapleys Hill Road, Seaton SA 5023', 'lat' => -34.8956, 'lng' => 138.5234],
        ['name' => 'Fulham Gardens Office Park', 'address' => '67 Fullwood Avenue, Fulham Gardens SA 5024', 'lat' => -34.9123, 'lng' => 138.5167],
        
        // Eastern Metro
        ['name' => 'Campbelltown Shopping Centre', 'address' => '567 Lower North East Road, Campbelltown SA 5074', 'lat' => -34.8567, 'lng' => 138.6789],
        ['name' => 'Paradise Business Hub', 'address' => '89 Lower North East Road, Paradise SA 5075', 'lat' => -34.8234, 'lng' => 138.6567],
        ['name' => 'Rostrevor Medical Plaza', 'address' => '145 Rostrevor Parade, Rostrevor SA 5073', 'lat' => -34.8456, 'lng' => 138.6890],
        
        // Southern Metro
        ['name' => 'Happy Valley Shopping Centre', 'address' => '123 Main South Road, Happy Valley SA 5159', 'lat' => -35.0456, 'lng' => 138.5678],
        ['name' => 'Morphett Vale Office Complex', 'address' => '234 Main South Road, Morphett Vale SA 5162', 'lat' => -35.1234, 'lng' => 138.5234],
        ['name' => 'Christie Downs Medical Centre', 'address' => '67 Beach Road, Christie Downs SA 5164', 'lat' => -35.1345, 'lng' => 138.4967],
        
        // Western Metro
        ['name' => 'Beverley Shopping Centre', 'address' => '145 Port Road, Beverley SA 5009', 'lat' => -34.9012, 'lng' => 138.5456],
        ['name' => 'Hindmarsh Business Park', 'address' => '234 Port Road, Hindmarsh SA 5007', 'lat' => -34.9089, 'lng' => 138.5678],
        ['name' => 'Royal Park Medical Hub', 'address' => '89 Cheltenham Parade, Royal Park SA 5014', 'lat' => -34.8567, 'lng' => 138.5234],
        
        // Additional Northern locations
        ['name' => 'Blair Athol Shopping Complex', 'address' => '123 Prospect Road, Blair Athol SA 5084', 'lat' => -34.8678, 'lng' => 138.5890],
        ['name' => 'Northfield Business Centre', 'address' => '67 Hampstead Road, Northfield SA 5085', 'lat' => -34.8345, 'lng' => 138.6123],
        ['name' => 'Windsor Gardens Office Park', 'address' => '234 North East Road, Windsor Gardens SA 5087', 'lat' => -34.8456, 'lng' => 138.6345],
        
        // Specialty locations
        ['name' => 'Torrens Island Industrial Park', 'address' => '45 Torrens Road, Torrens Island SA 5019', 'lat' => -34.8123, 'lng' => 138.4789],
        ['name' => 'Edinburgh Parks Business Hub', 'address' => '123 Edinburgh North Road, Edinburgh Parks SA 5111', 'lat' => -34.7456, 'lng' => 138.6890],
        ['name' => 'Angle Vale Industrial Centre', 'address' => '89 Heaslip Road, Angle Vale SA 5117', 'lat' => -34.6789, 'lng' => 138.6567],
        
        // Final locations to complete 60
        ['name' => 'Aldgate Hills Medical Centre', 'address' => '67 Mount Barker Road, Aldgate SA 5154', 'lat' => -35.0123, 'lng' => 138.7345],
        ['name' => 'Blackwood Shopping Centre Office', 'address' => '234 Shepherd Hill Road, Blackwood SA 5051', 'lat' => -35.0234, 'lng' => 138.6234],
        ['name' => 'Mitcham Square Business Centre', 'address' => '145 Belair Road, Mitcham SA 5062', 'lat' => -34.9789, 'lng' => 138.6345],
        ['name' => 'Glen Osmond Road Professional Complex', 'address' => '456 Glen Osmond Road, Glen Osmond SA 5064', 'lat' => -34.9567, 'lng' => 138.6567],
    ];

    public function run(): void
    {
        $companies = Company::whereIn('slug', ['dimeo', 'corporate-clean', 'bio-green-family', 'adelaide-facility-services'])->get();
        
        $this->command->info('🏢 Creating locations for ' . $companies->count() . ' companies...');
        
        $locationIndex = 0;
        foreach ($companies as $company) {
            $this->command->info("📍 Creating 15 locations for {$company->name}...");
            
            for ($i = 0; $i < 15; $i++) {
                $locationData = $this->adelaideLocations[$locationIndex];
                
                Location::create([
                    'company_id' => $company->id,
                    'name' => $locationData['name'],
                    'description' => "Professional cleaning facility for {$company->name}",
                    'address' => $locationData['address'],
                    'latitude' => $locationData['lat'],
                    'longitude' => $locationData['lng'],
                    'radius' => rand(50, 200), // Random radius between 50-200 meters
                    'zoom' => rand(14, 18), // Random zoom level
                    'timezone' => 'Australia/Adelaide',
                    'is_active' => true,
                ]);
                
                $locationIndex++;
            }
            
            $this->command->info("✅ Created 15 locations for {$company->name}");
        }
        
        $this->command->info('🎉 Successfully created ' . ($companies->count() * 15) . ' locations across all companies!');
    }
}