<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Stationery & Office Supplies
            [
                'name' => 'Stationery & Office Supplies',
                'description' => 'Pens, papers, folders, and general office supplies',
                'children' => [
                    ['name' => 'Writing Materials', 'description' => 'Pens, pencils, markers, highlighters'],
                    ['name' => 'Paper Products', 'description' => 'A4 paper, notebooks, registers'],
                    ['name' => 'Files & Folders', 'description' => 'Files, folders, binders'],
                    ['name' => 'Office Accessories', 'description' => 'Staplers, punches, clips, pins'],
                ]
            ],
            
            // Furniture & Fixtures
            [
                'name' => 'Furniture & Fixtures',
                'description' => 'Desks, chairs, tables, and other furniture',
                'children' => [
                    ['name' => 'Student Furniture', 'description' => 'Student desks, chairs, benches'],
                    ['name' => 'Staff Furniture', 'description' => 'Office desks, chairs, cabinets'],
                    ['name' => 'Classroom Furniture', 'description' => 'Teacher tables, boards, shelves'],
                    ['name' => 'Storage Furniture', 'description' => 'Almirahs, racks, lockers'],
                ]
            ],
            
            // Computers & IT Equipment
            [
                'name' => 'Computers & IT Equipment',
                'description' => 'Computers, laptops, printers, and IT accessories',
                'children' => [
                    ['name' => 'Desktop Computers', 'description' => 'Desktop PCs and workstations'],
                    ['name' => 'Laptops & Tablets', 'description' => 'Laptops, tablets, iPads'],
                    ['name' => 'Printers & Scanners', 'description' => 'Printers, scanners, copiers'],
                    ['name' => 'Networking Equipment', 'description' => 'Routers, switches, cables'],
                    ['name' => 'IT Accessories', 'description' => 'Keyboards, mice, monitors, UPS'],
                ]
            ],
            
            // Lab Equipment
            [
                'name' => 'Laboratory Equipment',
                'description' => 'Science lab equipment and consumables',
                'children' => [
                    ['name' => 'Physics Lab', 'description' => 'Physics instruments and equipment'],
                    ['name' => 'Chemistry Lab', 'description' => 'Chemicals, glassware, burners'],
                    ['name' => 'Biology Lab', 'description' => 'Microscopes, specimens, models'],
                    ['name' => 'Lab Consumables', 'description' => 'Test tubes, petri dishes, reagents'],
                ]
            ],
            
            // Sports Equipment
            [
                'name' => 'Sports Equipment',
                'description' => 'Sports items and athletic equipment',
                'children' => [
                    ['name' => 'Outdoor Sports', 'description' => 'Football, cricket, basketball equipment'],
                    ['name' => 'Indoor Sports', 'description' => 'Table tennis, badminton, chess'],
                    ['name' => 'Athletic Equipment', 'description' => 'Track and field equipment'],
                    ['name' => 'Sports Accessories', 'description' => 'Nets, cones, whistles, jerseys'],
                ]
            ],
            
            // Library & Books
            [
                'name' => 'Library & Books',
                'description' => 'Books, magazines, and library supplies',
                'children' => [
                    ['name' => 'Textbooks', 'description' => 'Academic textbooks by subject'],
                    ['name' => 'Reference Books', 'description' => 'Dictionaries, encyclopedias, atlases'],
                    ['name' => 'Library Books', 'description' => 'Fiction, non-fiction, magazines'],
                    ['name' => 'Library Supplies', 'description' => 'Book covers, labels, library cards'],
                ]
            ],
            
            // Audio Visual Equipment
            [
                'name' => 'Audio Visual Equipment',
                'description' => 'Projectors, speakers, and AV equipment',
                'children' => [
                    ['name' => 'Projectors & Screens', 'description' => 'Multimedia projectors, screens'],
                    ['name' => 'Audio Systems', 'description' => 'Speakers, microphones, amplifiers'],
                    ['name' => 'Display Systems', 'description' => 'Smart boards, LED displays'],
                ]
            ],
            
            // Cleaning & Maintenance
            [
                'name' => 'Cleaning & Maintenance',
                'description' => 'Cleaning supplies and maintenance items',
                'children' => [
                    ['name' => 'Cleaning Supplies', 'description' => 'Detergents, mops, brooms, dusters'],
                    ['name' => 'Maintenance Tools', 'description' => 'Tools, hardware, repair items'],
                    ['name' => 'Hygiene Products', 'description' => 'Sanitizers, tissues, soap'],
                ]
            ],
            
            // Medical & First Aid
            [
                'name' => 'Medical & First Aid',
                'description' => 'Medical supplies and first aid equipment',
                'children' => [
                    ['name' => 'First Aid Supplies', 'description' => 'Bandages, antiseptics, medicines'],
                    ['name' => 'Medical Equipment', 'description' => 'Thermometers, BP monitors, stretchers'],
                ]
            ],
            
            // Kitchen & Canteen
            [
                'name' => 'Kitchen & Canteen',
                'description' => 'Kitchen equipment and canteen supplies',
                'children' => [
                    ['name' => 'Cooking Equipment', 'description' => 'Stoves, ovens, utensils'],
                    ['name' => 'Serving Items', 'description' => 'Plates, cups, trays, cutlery'],
                    ['name' => 'Food Supplies', 'description' => 'Dry foods, beverages, snacks'],
                ]
            ],
            
            // Electrical & Electronics
            [
                'name' => 'Electrical & Electronics',
                'description' => 'Electrical items and electronic equipment',
                'children' => [
                    ['name' => 'Electrical Fittings', 'description' => 'Switches, sockets, wires, bulbs'],
                    ['name' => 'Electronic Devices', 'description' => 'Calculators, cameras, phones'],
                    ['name' => 'Power Backup', 'description' => 'Generators, UPS, batteries'],
                ]
            ],
            
            // Transportation
            [
                'name' => 'Transportation',
                'description' => 'Vehicles and transport equipment',
                'children' => [
                    ['name' => 'School Buses', 'description' => 'School buses and vans'],
                    ['name' => 'Vehicle Parts', 'description' => 'Spare parts, tires, batteries'],
                    ['name' => 'Fuel & Lubricants', 'description' => 'Petrol, diesel, engine oil'],
                ]
            ],
            
            // Musical Instruments
            [
                'name' => 'Musical Instruments',
                'description' => 'Musical instruments and accessories',
                'children' => [
                    ['name' => 'String Instruments', 'description' => 'Guitars, violins, sitars'],
                    ['name' => 'Wind Instruments', 'description' => 'Flutes, harmoniums'],
                    ['name' => 'Percussion Instruments', 'description' => 'Drums, tabla, cymbals'],
                ]
            ],
            
            // Safety & Security
            [
                'name' => 'Safety & Security',
                'description' => 'Security equipment and safety items',
                'children' => [
                    ['name' => 'CCTV & Security', 'description' => 'CCTV cameras, DVRs, monitors'],
                    ['name' => 'Fire Safety', 'description' => 'Fire extinguishers, alarms, hoses'],
                    ['name' => 'Access Control', 'description' => 'Locks, biometric systems, gates'],
                ]
            ],
        ];

        $this->createCategories($categories);
    }

    /**
     * Recursively create categories and subcategories
     */
    private function createCategories(array $categories, $parentId = null): void
    {
        foreach ($categories as $index => $categoryData) {
            $category = ItemCategory::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'] ?? null,
                'parent_id' => $parentId,
                'sort_order' => $index,
                'is_active' => true,
            ]);

            // Create children if they exist
            if (isset($categoryData['children']) && is_array($categoryData['children'])) {
                $this->createCategories($categoryData['children'], $category->id);
            }
        }
    }
}