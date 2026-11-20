// Trek catalogue. `months` = best months (1-12), `tags` = experience menu groups
// (short and high-altitude are worked out from days and altitude).
// The first 6 show in "Featured Himalayan Treks" (order and badges from Figma).
// Figma lists some different prices (e.g. ABC USD 980, Gokyo USD 1,180); confirm the real ones.
// Prices, durations and departure dates are placeholders to be
// confirmed by Crest Himalaya (see copy/brand-kit.md section 9).
export const TREKS = [
  {
    id: "ebc", name: "Everest Base Camp Trek", cat: "popular", badge: "Best seller", area: "Everest region", level: "Moderate",
    days: 14, alt: "5,545 m", grade: "Moderate to challenging", price: 1350, img: "everest-alpenglow.jpg",
    region: "Khumbu", start: "Lukla", season: "Mar to May, Sep to Dec",
    months: [3, 4, 5, 9, 10, 11, 12], tags: ["classic"],
    summary: "The classic. Fly into Lukla, walk through Sherpa villages and monasteries, and stand beneath Everest at 5,364 m.",
    highlights: ["Sunrise over Everest from Kala Patthar", "Namche Bazaar and Tengboche Monastery", "Two full acclimatisation days"],
    itinerary: ["Day 1: Arrive Kathmandu, trip briefing", "Day 2: Fly to Lukla, walk to Phakding", "Days 3 to 4: Namche Bazaar and acclimatisation hike", "Days 5 to 7: Tengboche, Dingboche and acclimatisation", "Days 8 to 10: Lobuche, Base Camp, Kala Patthar", "Days 11 to 14: Descend to Lukla, fly out"],
  },
  {
    id: "acircuit", name: "Annapurna Circuit Trek", cat: "popular", badge: "Popular", area: "Annapurna region", level: "Moderate",
    days: 15, alt: "5,416 m", grade: "Moderate to challenging", price: 1200, img: "annapurna-sanctuary.jpg",
    region: "Annapurna", start: "Besisahar", season: "Mar to May, Oct to Nov",
    months: [3, 4, 5, 10, 11], tags: ["classic"],
    summary: "A full loop around the Annapurna massif, from green rice terraces to the high desert of Manang and over the Thorong La.",
    highlights: ["Thorong La pass at 5,416 m", "Manang and the Tilicho Lake side trip", "Muktinath temple and the Kali Gandaki gorge"],
    itinerary: ["Day 1: Arrive Kathmandu", "Day 2: Drive to Besisahar and Chame", "Days 3 to 6: Pisang, Manang, acclimatise", "Days 7 to 9: Yak Kharka, Thorong Phedi, cross Thorong La", "Days 10 to 12: Muktinath, Jomsom, fly to Pokhara", "Days 13 to 15: Pokhara, Kathmandu, departure"],
  },
  {
    id: "abc", name: "Annapurna Base Camp", cat: "popular", badge: "", area: "Annapurna region", level: "Easy to moderate",
    days: 11, alt: "4,130 m", grade: "Moderate", price: 890, img: "abc-sunset.jpg",
    region: "Annapurna", start: "Pokhara", season: "Mar to May, Sep to Dec",
    months: [3, 4, 5, 9, 10, 11, 12], tags: ["classic", "family"],
    summary: "Rhododendron forest, Gurung villages and a natural amphitheatre ringed by Annapurna I and Machapuchare.",
    highlights: ["Sunrise inside the Annapurna Sanctuary", "Natural hot springs at Jhinu Danda", "Lower altitude, big mountain drama"],
    itinerary: ["Day 1: Arrive Kathmandu", "Day 2: Drive or fly to Pokhara", "Days 3 to 5: Ghandruk, Chhomrong, Bamboo", "Days 6 to 7: Deurali to Base Camp", "Days 8 to 9: Descend to Jhinu hot springs", "Days 10 to 11: Pokhara, Kathmandu"],
  },
  {
    id: "gokyo", name: "Gokyo Lakes & Renjo La", cat: "popular", badge: "", area: "Everest region", level: "Moderate",
    days: 12, alt: "5,360 m", grade: "Moderate to challenging", price: 1250, img: "gokyo-lake.jpg",
    region: "Khumbu", start: "Lukla", season: "Mar to May, Oct to Nov",
    months: [3, 4, 5, 10, 11], tags: ["classic"],
    summary: "Turquoise glacial lakes, the Ngozumpa glacier and one of the widest views of Everest, with a fraction of the crowds.",
    highlights: ["Six sacred Gokyo lakes", "Gokyo Ri panorama of four 8,000 m peaks", "Renjo La high pass"],
    itinerary: ["Day 1: Arrive Kathmandu", "Days 2 to 4: Lukla to Namche, acclimatise", "Days 5 to 7: Dole, Machhermo, Gokyo", "Day 8: Gokyo Ri sunrise", "Days 9 to 10: Renjo La to Thame", "Days 11 to 12: Lukla, fly out"],
  },
  {
    id: "mera", name: "Mera Peak Climb", cat: "peak", badge: "Adventure", area: "Everest region", level: "Challenging",
    days: 18, alt: "6,476 m", grade: "Strenuous", price: 2800, img: "mera-sunrise.jpg",
    region: "Hinku Valley", start: "Lukla", season: "Apr to May, Oct to Nov",
    months: [4, 5, 10, 11], tags: ["remote"],
    summary: "Nepal's highest trekking peak. A non-technical glacier climb with five 8,000 m giants on the summit horizon.",
    highlights: ["Summit view of Everest, Lhotse, Makalu, Cho Oyu, Kangchenjunga", "Glacier skills training at high camp", "Climbing permit and gear list included"],
    itinerary: ["Day 1: Arrive Kathmandu, gear check", "Days 2 to 7: Lukla through the Hinku valley", "Days 8 to 10: Khare, acclimatise, glacier training", "Days 11 to 13: High camp, summit, reserve day", "Days 14 to 17: Return via Zatrwa La", "Day 18: Fly out"],
  },
  {
    id: "manaslu", name: "Manaslu Circuit", cat: "popular", badge: "", area: "Manaslu region", level: "Challenging",
    days: 16, alt: "5,106 m", grade: "Challenging", price: 1690, img: "manaslu.jpg",
    region: "Gorkha", start: "Soti Khola", season: "Mar to May, Sep to Nov",
    months: [3, 4, 5, 9, 10, 11], tags: ["remote"],
    summary: "A restricted-area loop around the world's eighth highest peak, crossing the Larkya La into the Annapurna side.",
    highlights: ["Larkya La pass at 5,106 m", "Tibetan-Buddhist villages of Nubri and Tsum", "Restricted area permits handled"],
    itinerary: ["Day 1: Arrive Kathmandu", "Day 2: Drive to Soti Khola", "Days 3 to 8: Budhi Gandaki gorge to Samagaun", "Days 9 to 11: Acclimatise, Samdo, Dharamsala", "Day 12: Cross Larkya La", "Days 13 to 16: Bimthang, drive out"],
  },
  {
    id: "langtang", name: "Langtang Valley Trek", cat: "cultural", badge: "", area: "Langtang region", level: "Moderate",
    days: 10, alt: "4,984 m", grade: "Moderate", price: 950, img: "langtang.jpg",
    region: "Langtang", start: "Syabrubesi", season: "Mar to May, Oct to Dec",
    months: [3, 4, 5, 10, 11, 12], tags: ["family"],
    summary: "A drive, not a flight, into a glacier valley of Tamang culture, yak pastures and the Kyanjin Gompa.",
    highlights: ["No domestic flight needed", "Tserko Ri summit at 4,984 m", "Tamang heritage and cheese factory"],
    itinerary: ["Day 1: Arrive Kathmandu", "Day 2: Drive to Syabrubesi", "Days 3 to 5: Lama Hotel, Langtang, Kyanjin", "Day 6: Tserko Ri sunrise", "Days 7 to 8: Descend to Syabrubesi", "Days 9 to 10: Kathmandu, departure"],
  },
  {
    id: "island", name: "Island Peak Climb", cat: "peak", badge: "", area: "Everest region", level: "Challenging",
    days: 16, alt: "6,189 m", grade: "Strenuous", price: 2450, img: "island-peak.jpg",
    region: "Khumbu", start: "Lukla", season: "Apr to May, Oct to Nov",
    months: [4, 5, 10, 11], tags: [],
    summary: "Everest Base Camp plus a real summit. Fixed ropes, a short ladder and a summit ridge above the Imja glacier.",
    highlights: ["Combines EBC and a 6,000 m summit", "Rope and crampon training with guides", "1 guide to 2 climbers on summit day"],
    itinerary: ["Day 1: Arrive Kathmandu", "Days 2 to 7: Lukla to Dingboche with acclimatisation", "Days 8 to 10: Lobuche, Base Camp, Kala Patthar", "Days 11 to 12: Chhukung, climb training", "Day 13: Summit day", "Days 14 to 16: Descend, fly out"],
  },
  {
    id: "mustang", name: "Upper Mustang Trek", cat: "cultural", badge: "", area: "Mustang region", level: "Moderate",
    days: 14, alt: "4,010 m", grade: "Moderate", price: 2150, img: "mustang.jpg",
    region: "Mustang", start: "Jomsom", season: "Apr to Nov (rain shadow)",
    months: [4, 5, 6, 7, 8, 9, 10, 11], tags: ["remote"],
    summary: "A desert kingdom of red cliffs, cave monasteries and the walled city of Lo Manthang, open to trekkers since 1992.",
    highlights: ["Walled capital of Lo Manthang", "Ancient sky caves and monasteries", "Trekkable in the monsoon"],
    itinerary: ["Day 1: Arrive Kathmandu", "Days 2 to 3: Pokhara, fly to Jomsom, Kagbeni", "Days 4 to 7: Chele, Syangboche, Ghami, Tsarang", "Days 8 to 9: Lo Manthang and Chhoser caves", "Days 10 to 13: Return to Jomsom", "Day 14: Fly out"],
  },
  {
    id: "poonhill", name: "Ghorepani Poon Hill", cat: "short", badge: "", area: "Annapurna region", level: "Easy",
    days: 5, alt: "3,210 m", grade: "Easy", price: 490, img: "poon-hill.jpg",
    region: "Annapurna", start: "Pokhara", season: "All year, best Oct to Apr",
    months: [1, 2, 3, 4, 10, 11, 12], tags: ["family"],
    summary: "Five days, rhododendron forest and one of the best sunrises in Nepal over Dhaulagiri and Annapurna South.",
    highlights: ["Poon Hill sunrise panorama", "Perfect first Himalayan trek", "Families and all fitness levels"],
    itinerary: ["Day 1: Pokhara to Nayapul, walk to Tikhedhunga", "Day 2: Ghorepani", "Day 3: Poon Hill sunrise, Tadapani", "Day 4: Ghandruk village", "Day 5: Return to Pokhara"],
  },
  {
    id: "kanchenjunga", name: "Kanchenjunga Base Camp Trek", cat: "popular", badge: "", area: "Kanchenjunga region", level: "Challenging",
    days: 20, alt: "5,143 m", grade: "Challenging", price: 2400, img: "gokyo-valley.jpg",
    region: "Kanchenjunga", start: "Taplejung", season: "Mar to May, Oct to Nov",
    months: [3, 4, 5, 10, 11], tags: ["remote"],
    summary: "A wild, restricted-area trek to the foot of the world's third highest mountain, through Limbu and Sherpa villages far from the crowds.",
    highlights: ["Pangpema, Kanchenjunga north base camp at 5,143 m", "Remote villages and rhododendron forest", "Restricted area permits handled"],
    itinerary: ["Day 1: Arrive Kathmandu", "Days 2 to 3: Fly east, drive to Taplejung", "Days 4 to 10: Ghunsa via the Tamor valley", "Days 11 to 14: Kambachen, Lhonak, Pangpema", "Days 15 to 18: Return to Taplejung", "Days 19 to 20: Fly to Kathmandu, departure"],
  },
];

export const CATEGORIES = [
  { id: "all", label: "All treks" },
  { id: "trek", label: "Trekking" },
  { id: "popular", label: "Popular treks" },
  { id: "peak", label: "Peak climbing" },
  { id: "cultural", label: "Cultural tours" },
  { id: "short", label: "Short treks" },
];

// Everest Base Camp altitude profile (metres) for the scroll-drawn chart.
export const EBC_PROFILE = [
  { d: 1, place: "Kathmandu", m: 1400, note: "Arrive, meet your guide, gear check." },
  { d: 2, place: "Phakding", m: 2610, note: "Fly to Lukla at 2,860 m, easy walk down the Dudh Kosi." },
  { d: 3, place: "Namche Bazaar", m: 3440, note: "Cross the Hillary bridge, first view of Everest." },
  { d: 4, place: "Namche (rest)", m: 3440, acc: true, note: "Acclimatisation hike to the Everest View Hotel, 3,880 m." },
  { d: 5, place: "Tengboche", m: 3860, note: "Evening puja at the monastery beneath Ama Dablam." },
  { d: 6, place: "Dingboche", m: 4410, note: "Above the tree line. Stone walls and barley fields." },
  { d: 7, place: "Dingboche (rest)", m: 4410, acc: true, note: "Climb Nangkartshang, 5,083 m, sleep low." },
  { d: 8, place: "Lobuche", m: 4940, note: "Past the climbers' memorials at Thukla Pass." },
  { d: 9, place: "Base Camp", m: 5364, note: "Gorak Shep, then the Khumbu Icefall at Base Camp." },
  { d: 10, place: "Kala Patthar", m: 5545, peak: true, note: "Sunrise on Everest. The highest point of the trek." },
  { d: 11, place: "Pheriche", m: 4371, note: "Descend. Every step down feels easier." },
  { d: 12, place: "Namche Bazaar", m: 3440, note: "Bakery, hot shower, celebration." },
  { d: 13, place: "Lukla", m: 2860, note: "Last trail day. Thank-you dinner with your crew." },
  { d: 14, place: "Kathmandu", m: 1400, note: "Fly back, free afternoon in Thamel." },
];

// SAMPLE DEPARTURES: confirm real dates and seats. Used by the header menu, trek drawer and chatbot.
export const DEPARTURES = [
  { trek: "ebc", date: "2026-10-12", seats: 3 },
  { trek: "abc", date: "2026-10-18", seats: 6 },
  { trek: "gokyo", date: "2026-10-25", seats: 4 },
  { trek: "acircuit", date: "2026-10-28", seats: 7 },
  { trek: "island", date: "2026-11-02", seats: 2 },
  { trek: "ebc", date: "2026-11-09", seats: 8 },
  { trek: "mustang", date: "2026-11-15", seats: 7 },
  { trek: "manaslu", date: "2026-11-20", seats: 5 },
  { trek: "poonhill", date: "2026-12-06", seats: 10 },
  { trek: "langtang", date: "2027-03-14", seats: 9 },
  { trek: "kanchenjunga", date: "2027-03-28", seats: 6 },
  { trek: "mera", date: "2027-04-05", seats: 5 },
  { trek: "ebc", date: "2027-04-10", seats: 9 },
  { trek: "manaslu", date: "2027-04-12", seats: 8 },
];

// 1 = poor, 2 = fair, 3 = good, 4 = best
export const MONTHS = [
  { m: "Jan", r: 2, t: "Cold and clear. Great for lower treks like Poon Hill. High passes can be snowbound." },
  { m: "Feb", r: 2, t: "Cold nights, crisp views, quiet trails. Warming by the end of the month." },
  { m: "Mar", r: 4, t: "Spring begins. Rhododendrons bloom and the high trails open." },
  { m: "Apr", r: 4, t: "Peak spring season. Warm days, forests in flower, Everest expedition season." },
  { m: "May", r: 3, t: "Warmest month up high. Some afternoon cloud as the monsoon approaches." },
  { m: "Jun", r: 1, t: "Monsoon starts. Choose Upper Mustang, which sits in the rain shadow." },
  { m: "Jul", r: 1, t: "Monsoon. Lush and green, leeches below 3,000 m, flights often delayed." },
  { m: "Aug", r: 1, t: "Late monsoon. Upper Mustang still at its best." },
  { m: "Sep", r: 3, t: "Rains fade. Green valleys and clearing skies by late month." },
  { m: "Oct", r: 4, t: "The best month. Dry air, the clearest mountain views of the year." },
  { m: "Nov", r: 4, t: "Stable and clear. Colder nights and fewer crowds than October." },
  { m: "Dec", r: 3, t: "Crisp and quiet. Clear views, cold at altitude, trails to yourself." },
];

// Questions from Figma plus a few from the original site. Answers are drafts: please review.
export const FAQS = [
  { q: "Do I need previous trekking experience?", a: "No. Most of our treks suit first-timers who are reasonably fit. If you can walk 5 to 7 hours a day with a light daypack, you can do them. Peak climbs like Mera and Island Peak need good fitness, and we teach the rope and crampon skills on the trip." },
  { q: "What is the best time to trek in Nepal?", a: "Spring (March to May) and autumn (late September to November) bring the clearest skies and most stable weather. Winter is quiet and cold at altitude. In the June to August monsoon, Upper Mustang sits dry in the rain shadow." },
  { q: "Do I need travel insurance?", a: "Yes. Your policy must cover trekking up to the maximum altitude of your trip and emergency helicopter evacuation. We check it before departure." },
  { q: "What happens if I get altitude sickness?", a: "Our itineraries climb slowly with acclimatisation days, and guides check your blood oxygen every evening. At the first signs we slow down or descend. Guides carry first aid and oxygen on high routes, and we have helicopter partners if evacuation is ever needed." },
  { q: "Can I customize my itinerary?", a: "Yes. Every trek can run privately on your dates, and we can change the route, pace, rest days and accommodation. Use Plan a Custom Trek and tell us what you have in mind." },
  { q: "What is included in the trek price?", a: "Airport transfers, Kathmandu hotel, all permits, domestic flights on the itinerary, a licensed guide, porters, teahouse accommodation and three meals a day on the trail. International flights, visa, insurance, drinks and tips are not included." },
  { q: "How do I book and pay?", a: "Send an enquiry and we confirm availability within 24 hours. A 20% deposit secures your place and the balance is due on arrival in Kathmandu. We accept cards, PayPal, bank transfer, Western Union and Wise." },
  { q: "What should I pack?", a: "We send a full packing list after booking. The key items are broken-in boots, a four-season sleeping bag, a down jacket and layers. Most gear can also be rented in Kathmandu." },
];

// "Before You Trek" cards (from Figma). PLACEHOLDER LINKS: point each `href` at its guide page once written.
export const GUIDES = [
  { icon: "plane", t: "Nepal Visa", d: "Everything you need to know about arriving in Nepal.", href: "#" },
  { icon: "sun", t: "Best Time to Trek", d: "Choose the right season for your journey.", href: "#" },
  { icon: "pack", t: "Packing List", d: "What to bring, and what to leave behind.", href: "#" },
  { icon: "peak", t: "Altitude & Acclimatization", d: "Understanding high altitude safely.", href: "#" },
  { icon: "shield", t: "Travel Insurance", d: "The coverage every trekker needs.", href: "#" },
  { icon: "permit", t: "Trekking Permits", d: "A complete guide to Nepal's permit system.", href: "#" },
  { icon: "pin", t: "Getting to Nepal", d: "Flights, airport and Kathmandu.", href: "#" },
  { icon: "card", t: "Money & SIM Cards", d: "Practical info for your arrival.", href: "#" },
];

// Blog cards (content from Figma). PLACEHOLDER: swap `href` and `img` for the real posts.
export const POSTS = [
  { cat: "Guide", date: "2026-09-01", title: "Everest Base Camp Trek Cost: Complete 2026 Guide", excerpt: "Everything you need to know about budgeting for the world's most iconic trek, from permits to teahouses.", img: "everest-alpenglow.jpg", href: "#" },
  { cat: "Planning", date: "2026-08-01", title: "Best Time for Everest Base Camp Trek", excerpt: "Spring vs autumn: a detailed comparison of weather, crowds, visibility and trail conditions.", img: "prayer-flags.jpg", href: "#" },
  { cat: "Gear", date: "2026-08-01", title: "What to Pack for Nepal Trekking", excerpt: "A practical, tested packing list from our guides: what you actually need versus what you can leave home.", img: "ebc-trekkers.jpg", href: "#" },
];

// Videos and reels. PLACEHOLDER: replace each `url` with the real YouTube video / Short link,
// and `img` with its thumbnail (https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg works).
const YT = "https://www.youtube.com/@cresthimalaya";
export const VIDEOS = [
  { title: "Everest Base Camp in 14 days", img: "everest-alpenglow.jpg", url: YT },
  { title: "Inside the Annapurna Sanctuary", img: "annapurna-sanctuary.jpg", url: YT },
  { title: "Summit day on Mera Peak", img: "mera-sunrise.jpg", url: YT },
];
export const REELS = [
  { title: "Sunrise at Kala Patthar", img: "ama-dablam.jpg", url: YT + "/shorts" },
  { title: "A day with our porters", img: "yaks-ama-dablam.jpg", url: YT + "/shorts" },
  { title: "Gokyo's turquoise lakes", img: "gokyo-lake.jpg", url: YT + "/shorts" },
  { title: "Evening puja at Tengboche", img: "tengboche.jpg", url: YT + "/shorts" },
  { title: "Crossing the Larkya La", img: "manaslu.jpg", url: YT + "/shorts" },
];

// Header menus. Each link does one thing:
//   filter: "region:Everest" | "exp:short" | a CATEGORIES id  -> filters the trek grid
//   trek: "<id>" -> opens that trek;  plan: "<note>" -> opens the enquiry form with a note
//   chat: "<topic>" -> opens the assistant on that topic;  href: "#section" -> scrolls there
// PLACEHOLDER: swap these for real page URLs once trek, tour and guide pages exist.
// `k` / `lk` / `tk` / `dk` are interface-text keys (translated in the CMS); the English text is the fallback.
// `peaks: true` lists the CMS peak climbs; a link with only `trek` takes its label from the trek name.
export const MENU = [
  { label: "Treks", lk: "menu.treks", mega: [
    { title: "By Region", tk: "menu.by_region", links: [
      { t: "Everest", filter: "region:Everest" }, { t: "Annapurna", filter: "region:Annapurna" }, { t: "Langtang", filter: "region:Langtang" },
      { t: "Manaslu", filter: "region:Manaslu" }, { t: "Mustang", filter: "region:Mustang" }, { t: "Kanchenjunga", filter: "region:Kanchenjunga" },
    ] },
    { title: "By Experience", tk: "menu.by_experience", links: [
      { t: "Classic Treks", k: "exp.classic", filter: "exp:classic" }, { t: "Short Treks", k: "exp.short", filter: "exp:short" }, { t: "High Altitude", k: "exp.high", filter: "exp:high" },
      { t: "Remote Treks", k: "exp.remote", filter: "exp:remote" }, { t: "Family Treks", k: "exp.family", filter: "exp:family" },
    ] },
    { title: "Popular", tk: "menu.popular", links: [{ trek: "ebc" }, { trek: "acircuit" }, { trek: "abc" }, { trek: "manaslu" }] },
    { title: "Quick Links", tk: "menu.quick_links", links: [
      { t: "Fixed Departures", k: "menu.fixed_departures", chat: "availability" }, { t: "Custom Journeys", k: "menu.custom_journeys", plan: "I'd like a custom journey." }, { t: "Trekking Permits", k: "menu.permits", href: "#prepare" },
    ], cta: { t: "View All Treks", k: "menu.view_all", filter: "all" } },
  ] },
  { label: "Peak Climbing", lk: "menu.peak", peaks: true, links: [
    { t: "All peak climbs", k: "menu.all_peaks", filter: "peak" }, { t: "Bigger expeditions", k: "menu.expeditions", d: "Ask our team", dk: "menu.ask_team", chat: "expeditions" },
  ] },
  { label: "Tours", lk: "menu.tours", links: [
    { t: "Kathmandu Heritage Tour", k: "menu.tour.kathmandu", d: "Temples, stupas and old Durbar squares", dk: "menu.tour.kathmandu_d", plan: "I'm interested in the Kathmandu Heritage Tour." },
    { t: "Pokhara Lakeside Escape", k: "menu.tour.pokhara", d: "Lakes, sunrise views and paragliding", dk: "menu.tour.pokhara_d", plan: "I'm interested in a Pokhara lakeside tour." },
    { t: "Chitwan Jungle Safari", k: "menu.tour.chitwan", d: "Rhinos, elephants and river trips", dk: "menu.tour.chitwan_d", plan: "I'm interested in a Chitwan jungle safari." },
    { t: "Lumbini Pilgrimage", k: "menu.tour.lumbini", d: "Birthplace of the Buddha", dk: "menu.tour.lumbini_d", plan: "I'm interested in a Lumbini tour." },
    { t: "Cultural treks", k: "menu.tour.cultural", d: "Mustang, Langtang and more", dk: "menu.tour.cultural_d", filter: "cultural" },
  ] },
  { label: "Fixed Departures", lk: "menu.departures", departures: true },
  { label: "Travel Guide", lk: "menu.guide", guides: true, links: [
    { t: "Nepal Visa", k: "menu.guide.visa", href: "#prepare" }, { t: "Best Time to Trek", k: "menu.guide.season", href: "#prepare" }, { t: "Packing List", k: "menu.guide.packing", chat: "packing" },
    { t: "Altitude & Acclimatization", k: "menu.guide.altitude", href: "#prepare" }, { t: "Travel Insurance", k: "menu.guide.insurance", href: "#prepare" }, { t: "Trekking Permits", k: "menu.guide.permits", href: "#prepare" },
    { t: "Getting to Nepal", k: "menu.guide.getting", chat: "travel" }, { t: "FAQs", k: "menu.guide.faqs", href: "#faq" },
  ] },
  { label: "About", lk: "menu.about", links: [
    { t: "Why Crest Himalaya", k: "menu.about.why", href: "#why" }, { t: "Our Team", k: "menu.about.team", href: "#crew" }, { t: "Journey Support", k: "menu.about.support", href: "#support" },
    { t: "Traveler Stories", k: "menu.about.stories", href: "#stories" }, { t: "Blog", k: "menu.about.blog", href: "#blog" }, { t: "Contact", k: "menu.about.contact", href: "#start" },
  ] },
];

// Assistant facts. PLACEHOLDER values are marked: confirm with Crest Himalaya before launch.
// English treks / FAQs for the chat assistant when the website is in another language (filled by cms.js)
export const CHAT_DATA = { treks: null, faqs: null };

export const CHAT_INFO = {
  whatsapp: "9779862221697", phone: "+977 986 222 1697", phone2: "+977 981 327 2747", email: "info@cresthimalaya.com",
  hours: "Our team replies 7 days a week, usually within a few hours (Nepal time).", // PLACEHOLDER: confirm office hours
  deposit: "A 20% deposit secures your place. The balance is due on arrival in Kathmandu.",
  payments: "Visa, Mastercard, Amex, PayPal, bank transfer, Western Union and Wise.",
  offers: ["Lifetime discount for returning guests", "Group pricing for 4 or more travellers"], // PLACEHOLDER: confirm offers
  meeting: "We meet you at Tribhuvan International Airport (KTM) arrivals with a Crest Himalaya name board and drive you to your hotel.",
  briefing: "Your trip briefing and gear check are at your Kathmandu hotel, usually the evening you arrive or the next morning.", // PLACEHOLDER: confirm
  reporting: "On flight days to Lukla or Pokhara your guide collects you from the hotel early, often around 5:30 to 6:00 am. Exact times are on your trip sheet.", // PLACEHOLDER: confirm
  hotel: "A Kathmandu hotel is included before and after the trek. On the trail you stay in teahouses (simple mountain lodges), with comfort or luxury lodges available on some routes.",
  emergency: [
    "On the trail, tell your guide first. They carry first aid and a satellite phone on remote routes, and coordinate with our 24/7 Kathmandu desk.",
    "Helicopter evacuation is arranged through your travel insurance with our rescue partners.",
    "Nepal Police: 100 · Ambulance: 102 · Tourist Police: 1144",
  ],
  packing: [
    ["Clothing", "Base layers, fleece, down jacket, waterproof shell, trekking trousers, warm hat, sun hat, gloves"],
    ["Footwear", "Broken-in waterproof trekking boots, camp shoes, 3 to 4 pairs of trekking socks"],
    ["Sleeping", "Four-season sleeping bag (rent in Kathmandu), liner"],
    ["Gear", "35 to 45 L daypack, trekking poles, headtorch, water bottles and purification, sunglasses"],
    ["Health", "Personal medicines, blister kit, sunscreen SPF 50, lip balm, hand sanitiser"],
    ["Documents", "Passport, visa fee in cash, insurance details, 4 passport photos"],
  ],
  fitness: "If you can walk 5 to 7 hours a day with a light daypack, you can do most treks. Train 8 to 12 weeks ahead with hill walks, stairs and cardio. Peak climbs need stronger fitness, and we send a training plan.",
};
