<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Departure;
use App\Models\Faq;
use App\Models\Guide;
use App\Models\Post;
use App\Models\Review;
use App\Models\Section;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Trek;
use App\Models\Video;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

/**
 * Initial content: the current Crest Himalaya website copy (English), moved into the CMS.
 * Safe to run again: it only fills empty tables / missing rows and never overwrites edits.
 */
class ContentSeeder extends Seeder
{
    private function en(mixed $v): ?array
    {
        return $v === null || $v === '' || $v === [] ? null : ['en' => $v];
    }

    private function img(string $file): string
    {
        return 'uploads/seed/'.$file;
    }

    public function run(): void
    {
        $this->settings();
        $this->sections();
        $this->treks();
        $this->categories();
        $this->team();
        $this->reviews();
        $this->guides();
        $this->posts();
        $this->faqs();
        $this->videos();
        $this->menu();
    }

    /** Header menus and footer columns, as on the original website. */
    private function menu(): void
    {
        if (\App\Models\MenuItem::count()) {
            return;
        }
        // [title, location, style, links: [title, action, target, subtitle?, column?, button?]]
        $menus = [
            ['Treks', 'header', 'mega', [
                ['Everest', 'filter', 'region:Everest', null, 'By Region'], ['Annapurna', 'filter', 'region:Annapurna', null, 'By Region'],
                ['Langtang', 'filter', 'region:Langtang', null, 'By Region'], ['Manaslu', 'filter', 'region:Manaslu', null, 'By Region'],
                ['Mustang', 'filter', 'region:Mustang', null, 'By Region'], ['Kanchenjunga', 'filter', 'region:Kanchenjunga', null, 'By Region'],
                ['Classic Treks', 'filter', 'exp:classic', null, 'By Experience'], ['Short Treks', 'filter', 'exp:short', null, 'By Experience'],
                ['High Altitude', 'filter', 'exp:high', null, 'By Experience'], ['Remote Treks', 'filter', 'exp:remote', null, 'By Experience'],
                ['Family Treks', 'filter', 'exp:family', null, 'By Experience'],
                ['Everest Base Camp', 'trek', 'ebc', null, 'Popular'], ['Annapurna Circuit', 'trek', 'acircuit', null, 'Popular'],
                ['Annapurna Base Camp', 'trek', 'abc', null, 'Popular'], ['Manaslu Circuit', 'trek', 'manaslu', null, 'Popular'],
                ['Fixed Departures', 'chat', 'availability', null, 'Quick Links'], ['Custom Journeys', 'plan', "I'd like a custom journey.", null, 'Quick Links'],
                ['Trekking Permits', 'section', 'prepare', null, 'Quick Links'], ['View All Treks', 'filter', 'all', null, 'Quick Links', true],
            ]],
            ['Peak Climbing', 'header', 'peaks', [
                ['All peak climbs', 'filter', 'peak'], ['Bigger expeditions', 'chat', 'expeditions', 'Ask our team'],
            ]],
            ['Tours', 'header', 'list', [
                ['Kathmandu Heritage Tour', 'plan', "I'm interested in the Kathmandu Heritage Tour.", 'Temples, stupas and old Durbar squares'],
                ['Pokhara Lakeside Escape', 'plan', "I'm interested in a Pokhara lakeside tour.", 'Lakes, sunrise views and paragliding'],
                ['Chitwan Jungle Safari', 'plan', "I'm interested in a Chitwan jungle safari.", 'Rhinos, elephants and river trips'],
                ['Lumbini Pilgrimage', 'plan', "I'm interested in a Lumbini tour.", 'Birthplace of the Buddha'],
                ['Cultural treks', 'filter', 'cultural', 'Mustang, Langtang and more'],
            ]],
            ['Fixed Departures', 'header', 'departures', [
                ['See all dates & seats', 'chat', 'availability', 'Ask the assistant'],
            ]],
            ['Travel Guide', 'header', 'guides', [
                ['Visit Travel Guide', 'guides', null], ['FAQs', 'section', 'faq'],
            ]],
            ['About', 'header', 'list', [
                ['Why Crest Himalaya', 'section', 'why'], ['Our Team', 'section', 'crew'], ['Journey Support', 'section', 'support'],
                ['Traveler Stories', 'section', 'stories'], ['Blog', 'section', 'blog'], ['Contact', 'section', 'start'],
            ]],
            ['Explore', 'footer', 'list', [
                ['Signature treks', 'section', 'journeys'], ['Tailor-made trips', 'section', 'plan'], ['Why Crest', 'section', 'why'],
                ['Prepare', 'section', 'prepare'], ['Blog', 'section', 'blog'], ['FAQ', 'section', 'faq'],
            ]],
            ['Popular treks', 'footer', 'list', [
                ['Everest Base Camp Trek', 'trek', 'ebc'], ['Annapurna Circuit Trek', 'trek', 'acircuit'], ['Annapurna Base Camp', 'trek', 'abc'],
                ['Gokyo Lakes & Renjo La', 'trek', 'gokyo'], ['Mera Peak Climb', 'trek', 'mera'],
            ]],
        ];
        foreach ($menus as $i => [$title, $location, $style, $links]) {
            $menu = \App\Models\MenuItem::create(['title' => $this->en($title), 'location' => $location, 'style' => $style, 'sort_order' => $i + 1]);
            foreach ($links as $j => $l) {
                \App\Models\MenuItem::create([
                    'parent_id' => $menu->id, 'title' => $this->en($l[0]), 'action' => $l[1], 'target' => $l[2] ?? null,
                    'subtitle' => $this->en($l[3] ?? null), 'column' => $this->en($l[4] ?? null), 'is_button' => (bool) ($l[5] ?? false),
                    'sort_order' => $j + 1,
                ]);
            }
        }
    }

    private function settings(): void
    {
        $settings = [
            'company_name' => 'Crest Himalaya Treks & Expeditions',
            'brand_name' => 'CREST HIMALAYA',
            'brand_sub' => $this->en('TREKS & EXPEDITIONS'),
            'seo_title' => $this->en('Crest Himalaya Treks & Expeditions | Sherpa-led Treks in Nepal'),
            'seo_description' => $this->en('Sherpa-led treks and expeditions in Nepal. Everest Base Camp, Annapurna, Gokyo, Manaslu, Mera Peak and more. Small groups, transparent prices, safety first. Beyond Trails. Into The Himalayas.'),
            'photo_credits' => SiteContent::defaultPhotoCredits(),
            'associations' => [
                "TAAN | Trekking Agencies' Association of Nepal | https://www.taan.org.np/",
                'NTB | Nepal Tourism Board | https://ntb.gov.np/',
            ],
            'email' => 'info@cresthimalaya.com',
            'phone' => '+977 986 222 1697',
            'phone2' => '+977 981 327 2747',
            'whatsapp' => '9779862221697',
            'address' => $this->en('Kathmandu, Nepal'),
            'office_hours' => $this->en('Our team replies 7 days a week, usually within a few hours (Nepal time).'),
            'instagram' => 'https://instagram.com/cresthimalaya',
            'facebook' => 'https://facebook.com/cresthimalaya',
            'youtube' => 'https://www.youtube.com/@cresthimalaya',
            'tiktok' => 'https://www.tiktok.com/@cresthimalaya',
            'tripadvisor' => 'https://www.tripadvisor.com/',
            'google_reviews' => 'https://www.google.com/maps/search/Crest+Himalaya+Treks+%26+Expeditions',
            'footer_tagline' => $this->en('Beyond Trails. Into The Himalayas.'),
            'copyright' => $this->en('© 2026 Crest Himalaya Treks & Expeditions Pvt. Ltd. #Nepaltrekking #cresthimalaya'),
            'payment_methods' => ['VISA', 'MASTERCARD', 'AMEX', 'PAYPAL', 'BANK TRANSFER', 'WESTERN UNION', 'WISE'],
            'default_inclusions' => $this->en(['Airport transfers', 'Kathmandu hotel', 'All permits', 'Domestic flights on the route', 'Licensed guide', 'Porters', 'Teahouse lodging', 'Three meals a day on trail']),
            'deposit' => 'A 20% deposit secures your place. The balance is due on arrival in Kathmandu.',
            'payments' => 'Visa, Mastercard, Amex, PayPal, bank transfer, Western Union and Wise.',
            'offers' => ['Lifetime discount for returning guests', 'Group pricing for 4 or more travellers'],
            'meeting' => 'We meet you at Tribhuvan International Airport (KTM) arrivals with a Crest Himalaya name board and drive you to your hotel.',
            'briefing' => 'Your trip briefing and gear check are at your Kathmandu hotel, usually the evening you arrive or the next morning.',
            'reporting' => 'On flight days to Lukla or Pokhara your guide collects you from the hotel early, often around 5:30 to 6:00 am. Exact times are on your trip sheet.',
            'hotel' => 'A Kathmandu hotel is included before and after the trek. On the trail you stay in teahouses (simple mountain lodges), with comfort or luxury lodges available on some routes.',
            'emergency' => [
                'On the trail, tell your guide first. They carry first aid and a satellite phone on remote routes, and coordinate with our 24/7 Kathmandu desk.',
                'Helicopter evacuation is arranged through your travel insurance with our rescue partners.',
                'Nepal Police: 100 · Ambulance: 102 · Tourist Police: 1144',
            ],
            'packing' => [
                'Clothing: Base layers, fleece, down jacket, waterproof shell, trekking trousers, warm hat, sun hat, gloves',
                'Footwear: Broken-in waterproof trekking boots, camp shoes, 3 to 4 pairs of trekking socks',
                'Sleeping: Four-season sleeping bag (rent in Kathmandu), liner',
                'Gear: 35 to 45 L daypack, trekking poles, headtorch, water bottles and purification, sunglasses',
                'Health: Personal medicines, blister kit, sunscreen SPF 50, lip balm, hand sanitiser',
                'Documents: Passport, visa fee in cash, insurance details, 4 passport photos',
            ],
            'fitness' => 'If you can walk 5 to 7 hours a day with a light daypack, you can do most treks. Train 8 to 12 weeks ahead with hill walks, stairs and cardio. Peak climbs need stronger fitness, and we send a training plan.',
            // English only; the client adds languages in Admin -> Languages (translated automatically)
            'languages' => [['code' => 'en', 'name' => 'English', 'native' => 'English', 'rtl' => false, 'enabled' => true, 'translated' => true]],
        ];
        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function sections(): void
    {
        $e = fn ($v) => $this->en($v);
        $sections = [
            'hero' => [
                'eyebrow' => $e('Beyond Trails.'),
                'title' => $e(['Into The', 'Himalayas.']),
                'subtitle' => null,
                'values' => $e(['Sustainable Tourism', 'Support Local Communities', 'Preserve Natural Heritage', 'Safe & Professional']),
                'slides' => [
                    ['image' => $this->img('hero-poster.jpg'), 'caption' => $e('Everest region, Khumbu')],
                    ['image' => $this->img('ebc-trekkers.jpg'), 'caption' => $e('On the trail to Everest Base Camp')],
                    ['image' => $this->img('annapurna-sanctuary.jpg'), 'caption' => $e('Annapurna Sanctuary')],
                    ['image' => $this->img('namche.jpg'), 'caption' => $e('Namche Bazaar, 3,440 m')],
                    ['image' => $this->img('gokyo-lake.jpg'), 'caption' => $e('Gokyo Lakes')],
                ],
            ],
            'find' => ['eyebrow' => $e('Find your next adventure'), 'title' => $e('Find Your Himalayan Journey')],
            'treks' => ['eyebrow' => $e('Signature treks'), 'title' => $e('Featured Himalayan Treks')],
            'craft' => [
                'eyebrow' => $e('Craft your Crest experience'),
                'title' => $e('Your Journey.'),
                'title_em' => $e('Your Way.'),
                'lede' => $e("Don't want to follow a fixed itinerary or travel with strangers? We'll design a journey built entirely around you, from the first morning in Kathmandu to the final summit view."),
                'tags' => $e(['Your dates', 'Your pace', 'Your interests', 'Your group size', 'Your budget']),
                'button' => $e('Plan a Custom Trek'),
                'image' => $this->img('annapurna-south.jpg'),
                'stat_value' => '100%',
                'stat_label' => $e('Private & personal'),
            ],
            'why' => [
                'eyebrow' => $e('Why choose us'),
                'title' => $e('Why'),
                'title_em' => $e('Crest Himalaya?'),
                'lede' => $e('Nepal is not simply our destination. It is where our journeys begin. We combine local mountain knowledge, experienced professionals and thoughtful planning to create Himalayan experiences that are safe, authentic and deeply personal.'),
                'image' => $this->img('yaks-ama-dablam.jpg'),
                'quote' => $e("We know these mountains. We know how to take care of you. And we'll make the journey personal."),
                'quote_by' => $e('Nurbu Sherpa, Founder'),
                'items' => [
                    ['title' => $e('Local Expertise'), 'text' => $e('Our guides are born and raised in the communities they lead you through. Their knowledge of trails, weather, culture and people is not learned from books.')],
                    ['title' => $e('Safety & Support'), 'text' => $e('Certified wilderness first aid, satellite communication, careful acclimatisation protocols and 24/7 emergency coordination on every trek.')],
                    ['title' => $e('Personal Experiences'), 'text' => $e('No one-size-fits-all itineraries. Every detail of your journey, from pace to accommodation to route, is designed around you.')],
                    ['title' => $e('Responsible Travel'), 'text' => $e('Fair wages, porter welfare, minimum-impact practices and community investment are built into every trip we operate.')],
                ],
            ],
            'team' => ['eyebrow' => $e('The people'), 'title' => $e('Meet Your Himalayan Team')],
            'support' => [
                'eyebrow' => $e('Full journey support'),
                'title' => $e('Crest Journey Support'),
                'lede' => $e('We take care of you from Kathmandu until you return, at every step.'),
                'columns' => [
                    ['title' => $e('Before You Arrive'), 'items' => $e(['Airport pickup from Tribhuvan International', 'Welcome briefing in Kathmandu', 'Equipment guidance & gear checks', 'Full trip orientation'])],
                    ['title' => $e('In the Mountains'), 'items' => $e(['Licensed, experienced mountain guide', 'Curated teahouse accommodation', 'All meals on trail', 'Daily acclimatisation support'])],
                    ['title' => $e('When Things Change'), 'items' => $e(['Continuous weather monitoring', 'Wilderness first aid on hand', 'Emergency evacuation coordination', 'Satellite communication'])],
                    ['title' => $e('After the Trek'), 'items' => $e(['Kathmandu airport transfer', 'Trip certificate & memories', 'Feedback & future planning', 'Lifetime discount for returning guests'])],
                ],
            ],
            'stories' => ['eyebrow' => $e('Traveler Stories'), 'image' => $this->img('abc-sunset.jpg')],
            'prepare' => ['eyebrow' => $e('Prepare'), 'title' => $e('Before You Trek')],
            'blog' => ['eyebrow' => $e('From our blog'), 'title' => $e('Stories from the Himalayas')],
            'videos' => ['eyebrow' => $e('Videos & reels'), 'title' => $e('See the trail before you walk it.')],
            'faq' => [
                'eyebrow' => $e('Help'),
                'title' => $e('Frequently Asked Questions'),
                'lede' => $e("Can't find your answer? Message us on WhatsApp and a trek planner will reply, usually within a few hours."),
            ],
            'closing' => [
                'eyebrow' => $e('Start here'),
                'title' => $e('Ready to Go Beyond the Trails?'),
                'lede' => $e("Tell us what kind of Himalayan journey you're dreaming of. We'll help you make it happen."),
                'image' => $this->img('ebc-trekkers.jpg'),
            ],
        ];
        foreach ($sections as $key => $content) {
            Section::firstOrCreate(['key' => $key], ['content' => $content]);
        }
    }

    private function treks(): void
    {
        // [slug, name, cat, badge, area, level, days, alt, grade, price, img, region, start, season, months, tags, summary, highlights, itinerary, featured]
        $treks = [
            ['ebc', 'Everest Base Camp Trek', 'popular', 'Best seller', 'Everest region', 'Moderate', 14, 5545, 'Moderate to challenging', 1350, 'everest-alpenglow.jpg', 'Khumbu', 'Lukla', 'Mar to May, Sep to Dec', [3, 4, 5, 9, 10, 11, 12], ['classic'],
                'The classic. Fly into Lukla, walk through Sherpa villages and monasteries, and stand beneath Everest at 5,364 m.',
                ['Sunrise over Everest from Kala Patthar', 'Namche Bazaar and Tengboche Monastery', 'Two full acclimatisation days'],
                ['Day 1: Arrive Kathmandu, trip briefing', 'Day 2: Fly to Lukla, walk to Phakding', 'Days 3 to 4: Namche Bazaar and acclimatisation hike', 'Days 5 to 7: Tengboche, Dingboche and acclimatisation', 'Days 8 to 10: Lobuche, Base Camp, Kala Patthar', 'Days 11 to 14: Descend to Lukla, fly out'], true],
            ['acircuit', 'Annapurna Circuit Trek', 'popular', 'Popular', 'Annapurna region', 'Moderate', 15, 5416, 'Moderate to challenging', 1200, 'annapurna-sanctuary.jpg', 'Annapurna', 'Besisahar', 'Mar to May, Oct to Nov', [3, 4, 5, 10, 11], ['classic'],
                'A full loop around the Annapurna massif, from green rice terraces to the high desert of Manang and over the Thorong La.',
                ['Thorong La pass at 5,416 m', 'Manang and the Tilicho Lake side trip', 'Muktinath temple and the Kali Gandaki gorge'],
                ['Day 1: Arrive Kathmandu', 'Day 2: Drive to Besisahar and Chame', 'Days 3 to 6: Pisang, Manang, acclimatise', 'Days 7 to 9: Yak Kharka, Thorong Phedi, cross Thorong La', 'Days 10 to 12: Muktinath, Jomsom, fly to Pokhara', 'Days 13 to 15: Pokhara, Kathmandu, departure'], true],
            ['abc', 'Annapurna Base Camp', 'popular', '', 'Annapurna region', 'Easy to moderate', 11, 4130, 'Moderate', 890, 'abc-sunset.jpg', 'Annapurna', 'Pokhara', 'Mar to May, Sep to Dec', [3, 4, 5, 9, 10, 11, 12], ['classic', 'family'],
                'Rhododendron forest, Gurung villages and a natural amphitheatre ringed by Annapurna I and Machapuchare.',
                ['Sunrise inside the Annapurna Sanctuary', 'Natural hot springs at Jhinu Danda', 'Lower altitude, big mountain drama'],
                ['Day 1: Arrive Kathmandu', 'Day 2: Drive or fly to Pokhara', 'Days 3 to 5: Ghandruk, Chhomrong, Bamboo', 'Days 6 to 7: Deurali to Base Camp', 'Days 8 to 9: Descend to Jhinu hot springs', 'Days 10 to 11: Pokhara, Kathmandu'], true],
            ['gokyo', 'Gokyo Lakes & Renjo La', 'popular', '', 'Everest region', 'Moderate', 12, 5360, 'Moderate to challenging', 1250, 'gokyo-lake.jpg', 'Khumbu', 'Lukla', 'Mar to May, Oct to Nov', [3, 4, 5, 10, 11], ['classic'],
                'Turquoise glacial lakes, the Ngozumpa glacier and one of the widest views of Everest, with a fraction of the crowds.',
                ['Six sacred Gokyo lakes', 'Gokyo Ri panorama of four 8,000 m peaks', 'Renjo La high pass'],
                ['Day 1: Arrive Kathmandu', 'Days 2 to 4: Lukla to Namche, acclimatise', 'Days 5 to 7: Dole, Machhermo, Gokyo', 'Day 8: Gokyo Ri sunrise', 'Days 9 to 10: Renjo La to Thame', 'Days 11 to 12: Lukla, fly out'], true],
            ['mera', 'Mera Peak Climb', 'peak', 'Adventure', 'Everest region', 'Challenging', 18, 6476, 'Strenuous', 2800, 'mera-sunrise.jpg', 'Hinku Valley', 'Lukla', 'Apr to May, Oct to Nov', [4, 5, 10, 11], ['remote'],
                "Nepal's highest trekking peak. A non-technical glacier climb with five 8,000 m giants on the summit horizon.",
                ['Summit view of Everest, Lhotse, Makalu, Cho Oyu, Kangchenjunga', 'Glacier skills training at high camp', 'Climbing permit and gear list included'],
                ['Day 1: Arrive Kathmandu, gear check', 'Days 2 to 7: Lukla through the Hinku valley', 'Days 8 to 10: Khare, acclimatise, glacier training', 'Days 11 to 13: High camp, summit, reserve day', 'Days 14 to 17: Return via Zatrwa La', 'Day 18: Fly out'], true],
            ['manaslu', 'Manaslu Circuit', 'popular', '', 'Manaslu region', 'Challenging', 16, 5106, 'Challenging', 1690, 'manaslu.jpg', 'Gorkha', 'Soti Khola', 'Mar to May, Sep to Nov', [3, 4, 5, 9, 10, 11], ['remote'],
                "A restricted-area loop around the world's eighth highest peak, crossing the Larkya La into the Annapurna side.",
                ['Larkya La pass at 5,106 m', 'Tibetan-Buddhist villages of Nubri and Tsum', 'Restricted area permits handled'],
                ['Day 1: Arrive Kathmandu', 'Day 2: Drive to Soti Khola', 'Days 3 to 8: Budhi Gandaki gorge to Samagaun', 'Days 9 to 11: Acclimatise, Samdo, Dharamsala', 'Day 12: Cross Larkya La', 'Days 13 to 16: Bimthang, drive out'], true],
            ['langtang', 'Langtang Valley Trek', 'cultural', '', 'Langtang region', 'Moderate', 10, 4984, 'Moderate', 950, 'langtang.jpg', 'Langtang', 'Syabrubesi', 'Mar to May, Oct to Dec', [3, 4, 5, 10, 11, 12], ['family'],
                'A drive, not a flight, into a glacier valley of Tamang culture, yak pastures and the Kyanjin Gompa.',
                ['No domestic flight needed', 'Tserko Ri summit at 4,984 m', 'Tamang heritage and cheese factory'],
                ['Day 1: Arrive Kathmandu', 'Day 2: Drive to Syabrubesi', 'Days 3 to 5: Lama Hotel, Langtang, Kyanjin', 'Day 6: Tserko Ri sunrise', 'Days 7 to 8: Descend to Syabrubesi', 'Days 9 to 10: Kathmandu, departure'], false],
            ['island', 'Island Peak Climb', 'peak', '', 'Everest region', 'Challenging', 16, 6189, 'Strenuous', 2450, 'island-peak.jpg', 'Khumbu', 'Lukla', 'Apr to May, Oct to Nov', [4, 5, 10, 11], [],
                'Everest Base Camp plus a real summit. Fixed ropes, a short ladder and a summit ridge above the Imja glacier.',
                ['Combines EBC and a 6,000 m summit', 'Rope and crampon training with guides', '1 guide to 2 climbers on summit day'],
                ['Day 1: Arrive Kathmandu', 'Days 2 to 7: Lukla to Dingboche with acclimatisation', 'Days 8 to 10: Lobuche, Base Camp, Kala Patthar', 'Days 11 to 12: Chhukung, climb training', 'Day 13: Summit day', 'Days 14 to 16: Descend, fly out'], false],
            ['mustang', 'Upper Mustang Trek', 'cultural', '', 'Mustang region', 'Moderate', 14, 4010, 'Moderate', 2150, 'mustang.jpg', 'Mustang', 'Jomsom', 'Apr to Nov (rain shadow)', [4, 5, 6, 7, 8, 9, 10, 11], ['remote'],
                'A desert kingdom of red cliffs, cave monasteries and the walled city of Lo Manthang, open to trekkers since 1992.',
                ['Walled capital of Lo Manthang', 'Ancient sky caves and monasteries', 'Trekkable in the monsoon'],
                ['Day 1: Arrive Kathmandu', 'Days 2 to 3: Pokhara, fly to Jomsom, Kagbeni', 'Days 4 to 7: Chele, Syangboche, Ghami, Tsarang', 'Days 8 to 9: Lo Manthang and Chhoser caves', 'Days 10 to 13: Return to Jomsom', 'Day 14: Fly out'], false],
            ['poonhill', 'Ghorepani Poon Hill', 'short', '', 'Annapurna region', 'Easy', 5, 3210, 'Easy', 490, 'poon-hill.jpg', 'Annapurna', 'Pokhara', 'All year, best Oct to Apr', [1, 2, 3, 4, 10, 11, 12], ['family'],
                'Five days, rhododendron forest and one of the best sunrises in Nepal over Dhaulagiri and Annapurna South.',
                ['Poon Hill sunrise panorama', 'Perfect first Himalayan trek', 'Families and all fitness levels'],
                ['Day 1: Pokhara to Nayapul, walk to Tikhedhunga', 'Day 2: Ghorepani', 'Day 3: Poon Hill sunrise, Tadapani', 'Day 4: Ghandruk village', 'Day 5: Return to Pokhara'], false],
            ['kanchenjunga', 'Kanchenjunga Base Camp Trek', 'popular', '', 'Kanchenjunga region', 'Challenging', 20, 5143, 'Challenging', 2400, 'gokyo-valley.jpg', 'Kanchenjunga', 'Taplejung', 'Mar to May, Oct to Nov', [3, 4, 5, 10, 11], ['remote'],
                "A wild, restricted-area trek to the foot of the world's third highest mountain, through Limbu and Sherpa villages far from the crowds.",
                ['Pangpema, Kanchenjunga north base camp at 5,143 m', 'Remote villages and rhododendron forest', 'Restricted area permits handled'],
                ['Day 1: Arrive Kathmandu', 'Days 2 to 3: Fly east, drive to Taplejung', 'Days 4 to 10: Ghunsa via the Tamor valley', 'Days 11 to 14: Kambachen, Lhonak, Pangpema', 'Days 15 to 18: Return to Taplejung', 'Days 19 to 20: Fly to Kathmandu, departure'], false],
        ];

        $exclusions = ['International flights', 'Nepal visa fee', 'Travel insurance', 'Drinks and snacks', 'Tips for guides and porters'];
        foreach ($treks as $i => $t) {
            Trek::firstOrCreate(['slug' => $t[0]], [
                'name' => $this->en($t[1]), 'category' => $t[2], 'badge' => $this->en($t[3]), 'area' => $this->en($t[4]),
                'difficulty' => $this->en($t[5]), 'days' => $t[6], 'max_altitude' => $t[7], 'grade' => $this->en($t[8]),
                'price' => $t[9], 'image' => $this->img($t[10]), 'region' => $this->en($t[11]), 'start_point' => $this->en($t[12]),
                'season' => $this->en($t[13]), 'months' => $t[14], 'tags' => $t[15], 'summary' => $this->en($t[16]),
                'highlights' => $this->en($t[17]), 'itinerary' => $this->en($t[18]), 'exclusions' => $this->en($exclusions),
                'gallery' => [], 'is_featured' => $t[19], 'sort_order' => $i + 1, 'is_published' => true,
            ]);
        }

        // SAMPLE departures from the original site: confirm real dates and seats in the CMS.
        if (Departure::count() === 0) {
            $deps = [['ebc', '2026-10-12', 3], ['abc', '2026-10-18', 6], ['gokyo', '2026-10-25', 4], ['acircuit', '2026-10-28', 7], ['island', '2026-11-02', 2],
                ['ebc', '2026-11-09', 8], ['mustang', '2026-11-15', 7], ['manaslu', '2026-11-20', 5], ['poonhill', '2026-12-06', 10], ['langtang', '2027-03-14', 9],
                ['kanchenjunga', '2027-03-28', 6], ['mera', '2027-04-05', 5], ['ebc', '2027-04-10', 9], ['manaslu', '2027-04-12', 8]];
            $ids = Trek::pluck('id', 'slug');
            foreach ($deps as [$slug, $date, $seats]) {
                Departure::create(['trek_id' => $ids[$slug], 'date' => $date, 'seats' => $seats, 'is_published' => true]);
            }
        }
    }

    private function categories(): void
    {
        if (Category::count()) {
            return;
        }
        $rows = [
            ['Trekking', 'Classic Himalayan trails and iconic base camps.', 'Explore treks →', 'trek', 'up', 'ama-dablam.jpg'],
            ['Peak Climbing', 'Take your adventure beyond trekking.', 'Explore peaks →', 'peak', 'peak', 'island-peak.jpg'],
            ['Cultural Journeys', 'Discover Nepal beyond the mountains.', 'Explore tours →', 'cultural', 'circle', 'prayer-flags.jpg'],
            ['Custom Adventures', 'Your dates. Your pace. Your journey.', 'Plan your trip →', 'plan', 'star', 'ebc-trekkers.jpg'],
        ];
        foreach ($rows as $i => [$title, $desc, $link, $action, $icon, $img]) {
            Category::create(['title' => $this->en($title), 'description' => $this->en($desc), 'link_label' => $this->en($link), 'action' => $action, 'icon' => $icon, 'image' => $this->img($img), 'sort_order' => $i + 1]);
        }
    }

    private function team(): void
    {
        if (TeamMember::count()) {
            return;
        }
        $rows = [
            ['Nurbu Sherpa', 'Founder / Director', '20+ years experience', 'Everest · Annapurna · Manaslu', 'ebc-trekkers.jpg'],
            ['Tenzing Sherpa', 'Senior Trekking Guide', '15+ years experience', 'Everest · Annapurna · Langtang', 'namche.jpg'],
            ['Dawa Dorje', 'High-Altitude Guide', '12+ years experience', 'Manaslu · Mustang · Kanchenjunga', 'tengboche.jpg'],
        ];
        foreach ($rows as $i => [$name, $role, $xp, $regions, $bg]) {
            TeamMember::create(['name' => $name, 'designation' => $this->en($role), 'experience' => $this->en($xp), 'regions' => $this->en($regions), 'background' => $this->img($bg), 'sort_order' => $i + 1]);
        }
    }

    private function reviews(): void
    {
        if (Review::count()) {
            return;
        }
        // SAMPLE REVIEWS from the original site: replace with real guest reviews before launch.
        $rows = [
            ['Sample guest', 'Everest Base Camp Trek', 'Our guide read the mountain like a book. When I struggled above Lobuche he slowed everything down, and I made it to Kala Patthar for sunrise.'],
            ['Sample guest', 'Annapurna Base Camp', 'Every permit, flight and lodge was sorted before we landed. We just walked, ate dal bhat and stared at mountains.'],
            ['Sample guest', 'Mera Peak Climb', 'The glacier training day before Mera made the summit feel possible. Standing up there with five 8,000 m peaks around us was unreal.'],
        ];
        foreach ($rows as $i => [$name, $trek, $text]) {
            Review::create(['traveller_name' => $name, 'trek_label' => $this->en($trek), 'text' => $this->en($text), 'rating' => 5, 'sort_order' => $i + 1]);
        }
    }

    private function guides(): void
    {
        if (Guide::count()) {
            return;
        }
        $rows = [
            ['nepal-visa', 'plane', 'Nepal Visa', 'Everything you need to know about arriving in Nepal.',
                "Most nationalities can get a tourist visa on arrival at Tribhuvan International Airport in Kathmandu.\n\n## What to bring\n\n- A passport valid for at least six months\n- One passport photo\n- The visa fee in US dollars cash\n\nYou can also fill in the online arrival form before you fly to save time at the airport."],
            ['best-time-to-trek', 'sun', 'Best Time to Trek', 'Choose the right season for your journey.',
                "Spring (March to May) and autumn (late September to November) bring the clearest skies and the most stable weather.\n\n- **Spring:** warm days and rhododendrons in bloom\n- **Autumn:** dry air and the clearest mountain views of the year\n- **Winter:** quiet and cold at altitude; great for lower treks\n- **Monsoon (June to August):** Upper Mustang sits dry in the rain shadow"],
            ['packing-list', 'pack', 'Packing List', 'What to bring, and what to leave behind.',
                "Pack light: porters carry your main bag (usually up to 15 kg) and you carry a daypack.\n\nThe key items are broken-in boots, a four-season sleeping bag, a down jacket and layers. Most gear can be rented or bought in Kathmandu. We send a full personal list after booking."],
            ['altitude', 'peak', 'Altitude & Acclimatization', 'Understanding high altitude safely.',
                "Our itineraries climb slowly with acclimatisation days, and guides check your blood oxygen every evening.\n\nAt the first signs of altitude sickness we slow down or descend. Guides carry first aid, and oxygen on high routes."],
            ['travel-insurance', 'shield', 'Travel Insurance', 'The coverage every trekker needs.',
                'Your policy must cover trekking up to the maximum altitude of your trip and emergency helicopter evacuation. We check it before departure.'],
            ['trekking-permits', 'permit', 'Trekking Permits', "A complete guide to Nepal's permit system.",
                'We arrange all trekking permits for you, including national park entry, local area permits and restricted-area permits for Manaslu, Mustang and Kanchenjunga.'],
            ['getting-to-nepal', 'pin', 'Getting to Nepal', 'Flights, airport and Kathmandu.',
                'Fly into Tribhuvan International Airport (KTM) in Kathmandu. We meet you at arrivals with a Crest Himalaya name board and drive you to your hotel.'],
            ['money-and-sim', 'card', 'Money & SIM Cards', 'Practical info for your arrival.',
                'ATMs are easy to find in Kathmandu and Pokhara but rare on the trail, so carry Nepali rupees in cash for the trek. Local SIM cards with data are available at the airport with your passport.'],
        ];
        foreach ($rows as $i => [$slug, $icon, $title, $summary, $content]) {
            Guide::create(['slug' => $slug, 'icon' => $icon, 'title' => $this->en($title), 'summary' => $this->en($summary), 'content' => $this->en($content), 'sort_order' => $i + 1]);
        }
    }

    private function posts(): void
    {
        if (Post::count()) {
            return;
        }
        $rows = [
            ['everest-base-camp-trek-cost-2026', 'Guide', '2026-09-01', 'Everest Base Camp Trek Cost: Complete 2026 Guide', "Everything you need to know about budgeting for the world's most iconic trek, from permits to teahouses.", 'everest-alpenglow.jpg'],
            ['best-time-for-everest-base-camp-trek', 'Planning', '2026-08-01', 'Best Time for Everest Base Camp Trek', 'Spring vs autumn: a detailed comparison of weather, crowds, visibility and trail conditions.', 'prayer-flags.jpg'],
            ['what-to-pack-for-nepal-trekking', 'Gear', '2026-08-01', 'What to Pack for Nepal Trekking', 'A practical, tested packing list from our guides: what you actually need versus what you can leave home.', 'ebc-trekkers.jpg'],
        ];
        foreach ($rows as [$slug, $cat, $date, $title, $excerpt, $img]) {
            Post::create(['slug' => $slug, 'category' => $this->en($cat), 'published_at' => $date, 'title' => $this->en($title), 'excerpt' => $this->en($excerpt), 'image' => $this->img($img)]);
        }
    }

    private function faqs(): void
    {
        if (Faq::count()) {
            return;
        }
        $rows = [
            ['Do I need previous trekking experience?', 'No. Most of our treks suit first-timers who are reasonably fit. If you can walk 5 to 7 hours a day with a light daypack, you can do them. Peak climbs like Mera and Island Peak need good fitness, and we teach the rope and crampon skills on the trip.'],
            ['What is the best time to trek in Nepal?', 'Spring (March to May) and autumn (late September to November) bring the clearest skies and most stable weather. Winter is quiet and cold at altitude. In the June to August monsoon, Upper Mustang sits dry in the rain shadow.'],
            ['Do I need travel insurance?', 'Yes. Your policy must cover trekking up to the maximum altitude of your trip and emergency helicopter evacuation. We check it before departure.'],
            ['What happens if I get altitude sickness?', 'Our itineraries climb slowly with acclimatisation days, and guides check your blood oxygen every evening. At the first signs we slow down or descend. Guides carry first aid and oxygen on high routes, and we have helicopter partners if evacuation is ever needed.'],
            ['Can I customize my itinerary?', 'Yes. Every trek can run privately on your dates, and we can change the route, pace, rest days and accommodation. Use Plan a Custom Trek and tell us what you have in mind.'],
            ['What is included in the trek price?', 'Airport transfers, Kathmandu hotel, all permits, domestic flights on the itinerary, a licensed guide, porters, teahouse accommodation and three meals a day on the trail. International flights, visa, insurance, drinks and tips are not included.'],
            ['How do I book and pay?', 'Send an enquiry and we confirm availability within 24 hours. A 20% deposit secures your place and the balance is due on arrival in Kathmandu. We accept cards, PayPal, bank transfer, Western Union and Wise.'],
            ['What should I pack?', 'We send a full packing list after booking. The key items are broken-in boots, a four-season sleeping bag, a down jacket and layers. Most gear can also be rented in Kathmandu.'],
        ];
        foreach ($rows as $i => [$q, $a]) {
            Faq::create(['question' => $this->en($q), 'answer' => $this->en($a), 'sort_order' => $i + 1]);
        }
    }

    private function videos(): void
    {
        if (Video::count()) {
            return;
        }
        $yt = 'https://www.youtube.com/@cresthimalaya';
        $rows = [
            ['Everest Base Camp in 14 days', 'video', $yt, 'everest-alpenglow.jpg'],
            ['Inside the Annapurna Sanctuary', 'video', $yt, 'annapurna-sanctuary.jpg'],
            ['Summit day on Mera Peak', 'video', $yt, 'mera-sunrise.jpg'],
            ['Sunrise at Kala Patthar', 'reel', $yt.'/shorts', 'ama-dablam.jpg'],
            ['A day with our porters', 'reel', $yt.'/shorts', 'yaks-ama-dablam.jpg'],
            ["Gokyo's turquoise lakes", 'reel', $yt.'/shorts', 'gokyo-lake.jpg'],
            ['Evening puja at Tengboche', 'reel', $yt.'/shorts', 'tengboche.jpg'],
            ['Crossing the Larkya La', 'reel', $yt.'/shorts', 'manaslu.jpg'],
        ];
        foreach ($rows as $i => [$title, $type, $url, $img]) {
            Video::create(['title' => $this->en($title), 'type' => $type, 'url' => $url, 'image' => $this->img($img), 'sort_order' => $i + 1]);
        }
    }
}
