<?php
session_start();
include 'php/language_init.php';

// --- DETERMINE DASHBOARD LINK FOR "BACK" BUTTON ---
$dashboardLink = 'index.php';
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'farmer') {
        $dashboardLink = 'farmer_dashboard.php';
    } else {
        $dashboardLink = 'user_dashboard.php';
    }
}

// --- EXTENDED NEWS DATABASE (25+ Articles) ---
$news_articles = [
    [
        'id' => 1,
        'image' => 'https://droneag.farm/wp-content/uploads/2020/11/spreading.jpg',
        'category' => 'tech',
        'date' => '2025-11-28',
        'source' => 'AgriTech India',
        'title_en' => 'New Drone Technology for Pesticide Spraying',
        'desc_en' => 'Drones are revolutionizing how farmers spray pesticides, reducing cost and health risks by 40%.',
        'content_en' => 'The adoption of drone technology in Indian agriculture is transforming pest management. New DGCA-approved drones can cover 10 acres in just 20 minutes.',
        'title_hi' => 'कीटनाशक छिड़काव के लिए नई ड्रोन तकनीक',
        'desc_hi' => 'ड्रोन किसानों द्वारा कीटनाशकों के छिड़काव के तरीके में क्रांतिकारी बदलाव ला रहे हैं।',
        'content_hi' => 'भारतीय कृषि में ड्रोन तकनीक को अपनाना कीट प्रबंधन को बदल रहा है।',
        'title_mr' => 'कीटकनाशक फवारणीसाठी नवीन ड्रोन तंत्रज्ञान',
        'desc_mr' => 'ड्रोन शेतकऱ्यांच्या कीटकनाशक फवारणीच्या पद्धतीत क्रांती घडवत आहेत.',
        'content_mr' => 'भारतीय शेतीमध्ये ड्रोन तंत्रज्ञानाचा अवलंब कीड व्यवस्थापन बदलत आहे.'
    ],
    [
        'id' => 2,
        'image' => 'https://akm-img-a-in.tosshub.com/businesstoday/images/story/202310/ezgif-sixteen_nine_369.jpg?size=948:533',
        'category' => 'price',
        'date' => '2025-11-29',
        'source' => 'Market Watch',
        'title_en' => 'Onion Prices Expected to Drop Next Week',
        'desc_en' => 'With the new harvest arriving in Nashik markets, onion prices are likely to stabilize around ₹30/kg.',
        'content_en' => 'Consumer affairs ministry data suggests a sharp decline in retail onion prices as the Kharif crop hits the market.',
        'title_hi' => 'अगले सप्ताह प्याज की कीमतों में गिरावट की उम्मीद',
        'desc_hi' => 'नासिक की मंडियों में नई फसल आने से प्याज की कीमतें स्थिर होने की संभावना है।',
        'content_hi' => 'उपभोक्ता मामलों के मंत्रालय के आंकड़ों से पता चलता है कि कीमतें गिरेंगी।',
        'title_mr' => 'पुढील आठवड्यात कांद्याचे भाव घसरण्याची शक्यता',
        'desc_mr' => 'नाशिकच्या बाजारपेठेत नवीन आवक दाखल झाल्याने कांद्याचे भाव स्थिर होण्याची शक्यता आहे.',
        'content_mr' => 'खरीप पीक बाजारात आल्याने कांद्याच्या किरकोळ भावात मोठी घसरण होण्याची शक्यता आहे.'
    ],
    [
        'id' => 3,
        'image' => 'https://kj1bcdn.b-cdn.net/media/54351/pm-kusum-yojana.jpg',
        'category' => 'policy',
        'date' => '2025-11-25',
        'source' => 'Govt Notification',
        'title_en' => 'New Subsidy Announced for Solar Pumps',
        'desc_en' => 'The government has increased the subsidy for solar water pumps to 60% for small farmers.',
        'content_en' => 'Under PM-KUSUM scheme, farmers can now apply for solar pump installation with reduced upfront costs.',
        'title_hi' => 'सोलर पंपों के लिए नई सब्सिडी की घोषणा',
        'desc_hi' => 'सरकार ने छोटे किसानों के लिए सोलर वाटर पंप पर सब्सिडी बढ़ाकर 60% कर दी है।',
        'content_hi' => 'पीएम-कुसुम योजना के तहत किसान अब कम लागत पर आवेदन कर सकते हैं।',
        'title_mr' => 'सौर पंपांसाठी नवीन अनुदान जाहीर',
        'desc_mr' => 'सरकारने अल्पभूधारक शेतकऱ्यांसाठी सौर कृषी पंपांचे अनुदान ६० टक्क्यांपर्यंत वाढवले आहे.',
        'content_mr' => 'पीएम-कुसुम योजनेअंतर्गत शेतकरी आता कमी खर्चात सौर पंपासाठी अर्ज करू शकतात.'
    ],
    [
        'id' => 4,
        'image' => 'https://tse2.mm.bing.net/th/id/OIP.ykrlCIp8ZoIyWhwR-rTbyQHaE7?pid=Api&P=0&h=220',
        'category' => 'tech',
        'date' => '2025-11-27',
        'source' => 'Smart Farming',
        'title_en' => 'AI App Detects Crop Diseases from Photos',
        'desc_en' => 'A new free app helps farmers identify crop diseases simply by taking a photo with their phone.',
        'content_en' => 'Scientists have launched "KisanGuard", an AI app that detects early blight in tomatoes and potatoes.',
        'title_hi' => 'एआई ऐप तस्वीरों से फसल के रोगों का पता लगाता है',
        'desc_hi' => 'एक नया मुफ्त ऐप किसानों को फोटो खींचकर फसल रोगों की पहचान करने में मदद करता है।',
        'content_hi' => 'वैज्ञानिकों ने "किसानगार्ड" लॉन्च किया है, जो टमाटर और आलू में रोगों का पता लगाता है।',
        'title_mr' => 'एआय ॲप फोटोवरून पिकांचे रोग ओळखते',
        'desc_mr' => 'एक नवीन विनामूल्य ॲप शेतकऱ्यांना फोटो काढून पिकांचे रोग ओळखण्यास मदत करते.',
        'content_mr' => 'शास्त्रज्ञांनी "किसानगार्ड" हे एआय ॲप लाँच केले आहे जे पिकांवरील रोग ओळखते.'
    ],
    [
        'id' => 5,
        'image' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?auto=format&fit=crop&w=600&q=80',
        'category' => 'price',
        'date' => '2025-11-30',
        'source' => 'Kisan News',
        'title_en' => 'Wheat Export Ban Lifted by Government',
        'desc_en' => 'Good news for wheat farmers as exports open up to international markets.',
        'content_en' => 'Following a bumper harvest, the ban on wheat exports has been lifted, expected to boost domestic prices.',
        'title_hi' => 'सरकार ने गेहूं निर्यात पर लगा प्रतिबंध हटाया',
        'desc_hi' => 'गेहूं किसानों के लिए अच्छी खबर, अंतरराष्ट्रीय बाजारों में निर्यात खुला।',
        'content_hi' => 'बंपर फसल के बाद गेहूं निर्यात पर से प्रतिबंध हटा लिया गया है।',
        'title_mr' => 'सरकारने गव्हावरील निर्यातबंदी उठवली',
        'desc_mr' => 'गहू उत्पादक शेतकऱ्यांसाठी आनंदाची बातमी, आंतरराष्ट्रीय बाजारपेठेत निर्यात सुरू.',
        'content_mr' => 'भरघोस पिकानंतर गव्हाच्या निर्यातीवरील बंदी उठवण्यात आली आहे.'
    ],
    [
        'id' => 6,
        'image' => 'https://tse2.mm.bing.net/th/id/OIP.RZUnJrUZcoVY1aY_s-3XtQHaD3?pid=Api&P=0&h=220',
        'category' => 'weather',
        'date' => '2025-12-01',
        'source' => 'IMD Alert',
        'title_en' => 'Heavy Rain Warning for Coastal Regions',
        'desc_en' => 'IMD has issued a yellow alert for coastal districts due to a developing cyclone.',
        'content_en' => 'Farmers in coastal Maharashtra and Gujarat are advised to secure their harvested crops as heavy rainfall is expected over the next 48 hours.',
        'title_hi' => 'तटीय क्षेत्रों के लिए भारी बारिश की चेतावनी',
        'desc_hi' => 'आईएमडी ने चक्रवात के कारण तटीय जिलों के लिए येलो अलर्ट जारी किया है।',
        'content_hi' => 'तटीय महाराष्ट्र और गुजरात के किसानों को अपनी कटी हुई फसलों को सुरक्षित करने की सलाह दी जाती है।',
        'title_mr' => 'किनारपट्टीच्या भागांसाठी मुसळधार पावसाचा इशारा',
        'desc_mr' => 'चक्रीवादळामुळे हवामान खात्याने किनारपट्टीच्या जिल्ह्यांसाठी यलो अलर्ट जारी केला आहे.',
        'content_mr' => 'पुढील ४८ तासांत मुसळधार पावसाची शक्यता असल्याने शेतकऱ्यांनी पीक सुरक्षित ठेवावे.'
    ],
    [
        'id' => 7,
        'image' => 'https://images.unsplash.com/photo-1628352081506-83c43123ed6d?auto=format&fit=crop&w=600&q=80',
        'category' => 'policy',
        'date' => '2025-11-26',
        'source' => 'Agri Ministry',
        'title_en' => 'Organic Fertilizer Subsidy Increased',
        'desc_en' => 'To promote organic farming, subsidy on bio-fertilizers hiked by 25%.',
        'content_en' => 'The move aims to reduce dependency on chemical urea and improve soil health across the nation.',
        'title_hi' => 'जैविक खाद पर सब्सिडी बढ़ी',
        'desc_hi' => 'जैविक खेती को बढ़ावा देने के लिए जैव उर्वरकों पर सब्सिडी 25% बढ़ाई गई।',
        'content_hi' => 'इस कदम का उद्देश्य रासायनिक यूरिया पर निर्भरता कम करना है।',
        'title_mr' => 'सेंद्रिय खतांच्या अनुदानात वाढ',
        'desc_mr' => 'सेंद्रिय शेतीला चालना देण्यासाठी जैविक खतांवरील अनुदानात २५% वाढ.',
        'content_mr' => 'रासायनिक युरियावरील अवलंबित्व कमी करणे हा यामागील उद्देश आहे.'
    ],
    [
        'id' => 8,
        'image' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=600&q=80',
        'category' => 'tech',
        'date' => '2025-11-24',
        'source' => 'Agri Research',
        'title_en' => 'New Heat-Resistant Tomato Variety Launched',
        'desc_en' => 'Scientists developed "Ruby Heat", a tomato variety that thrives in 45°C temps.',
        'content_en' => 'This new variety ensures tomato production even during peak summer months, stabilizing prices.',
        'title_hi' => 'गर्मी प्रतिरोधी टमाटर की नई किस्म',
        'desc_hi' => 'वैज्ञानिकों ने "रूबी हीट" विकसित की है, जो 45 डिग्री तापमान में भी उगती है।',
        'content_hi' => 'यह नई किस्म भीषण गर्मी में भी टमाटर का उत्पादन सुनिश्चित करती है।',
        'title_mr' => 'टोमॅटोची उष्णता-प्रतिरोधक नवीन जात',
        'desc_mr' => 'शास्त्रज्ञांनी "रुबी हीट" ही ४५ अंश तापमानात टिकणारी टोमॅटोची जात विकसित केली आहे.',
        'content_mr' => 'ही नवीन जात कडक उन्हाळ्यातही टोमॅटोचे उत्पादन सुनिश्चित करते.'
    ],
    [
        'id' => 9,
        'image' => 'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?auto=format&fit=crop&w=600&q=80',
        'category' => 'dairy',
        'date' => '2025-11-30',
        'source' => 'Dairy Board',
        'title_en' => 'Milk Procurement Prices Hiked by ₹2',
        'desc_en' => 'Dairy cooperatives announce a price hike, benefiting millions of farmers.',
        'content_en' => 'Due to rising fodder costs, the procurement price for buffalo milk has been increased by ₹2 per liter.',
        'title_hi' => 'दूध खरीद की कीमतों में ₹2 की बढ़ोतरी',
        'desc_hi' => 'डेयरी सहकारी समितियों ने कीमतों में बढ़ोतरी की घोषणा की, जिससे किसानों को लाभ होगा।',
        'content_hi' => 'चारे की बढ़ती लागत के कारण भैंस के दूध के खरीद मूल्य में वृद्धि की गई है।',
        'title_mr' => 'दूध खरेदी दरात ₹२ ने वाढ',
        'desc_mr' => 'दुग्ध सहकारी संस्थांनी दरवाढ जाहीर केल्याने लाखो शेतकऱ्यांना फायदा होणार आहे.',
        'content_mr' => 'साऱ्याच्या वाढत्या खर्चामुळे म्हशीच्या दूध खरेदी दरात वाढ करण्यात आली आहे.'
    ],
    [
        'id' => 10,
        'image' => 'https://www.farmads.co.uk/wp-content/uploads/2023/05/electric-tractor-lamma-3.jpeg',
        'category' => 'tech',
        'date' => '2025-11-22',
        'source' => 'Tractor Junction',
        'title_en' => 'Electric Tractors Gain Popularity',
        'desc_en' => 'Sales of electric tractors have doubled this quarter due to low running costs.',
        'content_en' => 'Electric tractors offer a running cost of just ₹25 per hour compared to ₹150 for diesel ones.',
        'title_hi' => 'इलेक्ट्रिक ट्रैक्टरों की लोकप्रियता बढ़ी',
        'desc_hi' => 'कम परिचालन लागत के कारण इस तिमाही में इलेक्ट्रिक ट्रैक्टरों की बिक्री दोगुनी हो गई है।',
        'content_hi' => 'इलेक्ट्रिक ट्रैक्टर केवल ₹25 प्रति घंटे की लागत प्रदान करते हैं।',
        'title_mr' => 'इलेक्ट्रिक ट्रॅक्टरची लोकप्रियता वाढली',
        'desc_mr' => 'कमी खर्चामुळे या तिमाहीत इलेक्ट्रिक ट्रॅक्टरची विक्री दुप्पट झाली आहे.',
        'content_mr' => 'इलेक्ट्रिक ट्रॅक्टर डिझेलच्या तुलनेत खूप कमी खर्चात चालतात.'
    ],
    [
        'id' => 11,
        'image' => 'https://www.rightsofemployees.com/wp-content/uploads/2025/01/KCC-limit.png',
        'category' => 'policy',
        'date' => '2025-11-20',
        'source' => 'Govt News',
        'title_en' => 'Kisan Credit Card Limit Doubled',
        'desc_en' => 'The limit for collateral-free loans via KCC has been raised to ₹3 Lakhs.',
        'content_en' => 'This decision allows farmers to access more capital for seeds and fertilizers without mortgaging land.',
        'title_hi' => 'किसान क्रेडिट कार्ड की सीमा दोगुनी',
        'desc_hi' => 'केसीसी के माध्यम से बिना गारंटी ऋण की सीमा बढ़ाकर ₹3 लाख कर दी गई है।',
        'content_hi' => 'यह निर्णय किसानों को जमीन गिरवी रखे बिना अधिक पूंजी प्राप्त करने की अनुमति देता है।',
        'title_mr' => 'किसान क्रेडिट कार्डची मर्यादा दुप्पट',
        'desc_mr' => 'केसीसीद्वारे विनातारण कर्जाची मर्यादा ३ लाखांपर्यंत वाढवण्यात आली आहे.',
        'content_mr' => 'या निर्णयामुळे शेतकऱ्यांना जमीन गहाण न ठेवता अधिक भांडवल उपलब्ध होणार आहे.'
    ],
    [
        'id' => 12,
        'image' => 'https://commodity-board.com/wordpress/wp-content/uploads/2024/09/Turmeric-bumper-crop.jpg',
        'category' => 'crop',
        'date' => '2025-11-28',
        'source' => 'Agri Market',
        'title_en' => 'Record Turmeric Production Expected',
        'desc_en' => 'Telangana and Maharashtra report excellent turmeric crop conditions.',
        'content_en' => 'Favorable weather conditions have led to a projected 20% increase in turmeric yield this season.',
        'title_hi' => 'रिकॉर्ड हल्दी उत्पादन की उम्मीद',
        'desc_hi' => 'तेलंगाना और महाराष्ट्र में हल्दी की फसल की स्थिति उत्कृष्ट है।',
        'content_hi' => 'अनुकूल मौसम के कारण इस सीजन में हल्दी की पैदावार में 20% वृद्धि का अनुमान है।',
        'title_mr' => 'विक्रमी हळद उत्पादनाची अपेक्षा',
        'desc_mr' => 'तेलंगणा आणि महाराष्ट्रात हळद पिकाची स्थिती उत्तम असल्याचे वृत्त आहे.',
        'content_mr' => 'अनुकूल हवामानामुळे या हंगामात हळदीच्या उत्पादनात २०% वाढ होण्याचा अंदाज आहे.'
    ],
    [
        'id' => 13,
        'image' => 'https://tse4.mm.bing.net/th/id/OIP.F7NFBtSe2-E_hkR818dDGgHaFj?pid=Api&P=0&h=220',
        'category' => 'policy',
        'date' => '2025-11-18',
        'source' => 'Soil Health',
        'title_en' => 'Free Soil Testing Campaign Launched',
        'desc_en' => 'Mobile soil testing vans will visit 5000 villages next month.',
        'content_en' => 'Farmers can get their soil health cards updated instantly to determine the right fertilizer mix.',
        'title_hi' => 'निःशुल्क मृदा परीक्षण अभियान शुरू',
        'desc_hi' => 'मोबाइल मृदा परीक्षण वैन अगले महीने 5000 गांवों का दौरा करेंगी।',
        'content_hi' => 'किसान सही उर्वरक मिश्रण निर्धारित करने के लिए अपने मृदा स्वास्थ्य कार्ड अपडेट करा सकते हैं।',
        'title_mr' => 'मोफत माती परीक्षण मोहीम सुरू',
        'desc_mr' => 'फिरत्या माती परीक्षण प्रयोगशाळा पुढील महिन्यात ५००० गावांना भेटी देतील.',
        'content_mr' => 'शेतकरी योग्य खतांचे मिश्रण ठरवण्यासाठी त्यांचे मृदा आरोग्य कार्ड अपडेट करून घेऊ शकतात.'
    ],
    [
        'id' => 14,
        'image' => 'https://media.assettype.com/freepressjournal/2024-11-07/81csmb2a/7-Nov-%E2%80%93-Bhikangaon-Mandi.jpg',
        'category' => 'price',
        'date' => '2025-11-29',
        'source' => 'Cotton Corp',
        'title_en' => 'Cotton Prices Surge to ₹8000/Quintal',
        'desc_en' => 'Global demand pushes cotton prices higher, benefiting Indian growers.',
        'content_en' => 'Strong international demand for Indian textiles has driven raw cotton prices to a yearly high.',
        'title_hi' => 'कपास की कीमतें ₹8000/क्विंटल तक पहुंचीं',
        'desc_hi' => 'वैश्विक मांग ने कपास की कीमतों को बढ़ा दिया है, जिससे भारतीय उत्पादकों को लाभ हुआ है।',
        'content_hi' => 'भारतीय वस्त्रों की मजबूत अंतरराष्ट्रीय मांग ने कच्चे कपास की कीमतों को उच्च स्तर पर पहुंचा दिया है।',
        'title_mr' => 'कापसाचे भाव ₹८०००/क्विंटलवर',
        'desc_mr' => 'जागतिक मागणीमुळे कापसाचे भाव वधारले, भारतीय उत्पादकांना फायदा.',
        'content_mr' => 'भारतीय कापडाला असलेल्या आंतरराष्ट्रीय मागणीमुळे कापसाचे भाव उच्चांकी पातळीवर पोहोचले आहेत.'
    ],
    [
        'id' => 15,
        'image' => 'https://tse3.mm.bing.net/th/id/OIP.-Y7HHY-g8Z10SToqPPP3xQHaEx?pid=Api&P=0&h=220',
        'category' => 'price',
        'date' => '2025-11-25',
        'source' => 'Mandi News',
        'title_en' => 'Soybean MSP Procurement Begins',
        'desc_en' => 'Government centers open for Soybean procurement at MSP of ₹4600.',
        'content_en' => 'Farmers are advised to register online to sell their produce at government centers to avoid distress sales.',
        'title_hi' => 'सोयाबीन एमएसपी खरीद शुरू',
        'desc_hi' => 'सरकार ने ₹4600 के एमएसपी पर सोयाबीन खरीद केंद्र खोले।',
        'content_hi' => 'किसानों को सलाह दी जाती है कि वे अपनी उपज सरकारी केंद्रों पर बेचने के लिए ऑनलाइन पंजीकरण करें।',
        'title_mr' => 'सोयाबीन एमएसपी खरेदी सुरू',
        'desc_mr' => 'शासनाने ₹४६०० एमएसपी दराने सोयाबीन खरेदी केंद्र सुरू केले.',
        'content_mr' => 'शेतकऱ्यांनी आपला माल हमीभावाने विकण्यासाठी ऑनलाइन नोंदणी करण्याचे आवाहन करण्यात आले आहे.'
    ],
    [
        'id' => 16,
        'image' => 'https://tse2.mm.bing.net/th/id/OIP.xkyHoMGpWSqXGOWe8dW2zQHaEK?pid=Api&P=0&h=220',
        'category' => 'tech',
        'date' => '2025-11-26',
        'source' => 'Hydro India',
        'title_en' => 'Hydroponics Training for Youth',
        'desc_en' => 'State govt launches subsidized training for soil-less farming.',
        'content_en' => 'The program aims to encourage urban youth to take up modern agriculture with high-value crops like lettuce and berries.',
        'title_hi' => 'युवाओं के लिए हाइड्रोपोनिक्स प्रशिक्षण',
        'desc_hi' => 'राज्य सरकार ने मिट्टी रहित खेती के लिए सब्सिडी वाला प्रशिक्षण शुरू किया।',
        'content_hi' => 'इस कार्यक्रम का उद्देश्य शहरी युवाओं को आधुनिक कृषि अपनाने के लिए प्रोत्साहित करना है।',
        'title_mr' => 'तरुणांसाठी हायड्रोपोनिक्स प्रशिक्षण',
        'desc_mr' => 'राज्य सरकारने मातीविना शेतीसाठी अनुदानित प्रशिक्षण सुरू केले.',
        'content_mr' => 'शहरी तरुणांना आधुनिक शेतीकडे वळवणे हा या कार्यक्रमाचा मुख्य उद्देश आहे.'
    ],
    [
        'id' => 17,
        'image' => 'https://tse3.mm.bing.net/th/id/OIP.nAsuK58CWfdpmqmfwZi8UQHaE6?pid=Api&P=0&h=220',
        'category' => 'crop',
        'date' => '2025-11-21',
        'source' => 'Horticulture Dept',
        'title_en' => 'Grape Export Season Begins',
        'desc_en' => 'Nashik grapes set to depart for European markets next week.',
        'content_en' => 'Exporters are optimistic about good rates this year due to low production in competitor countries like Chile.',
        'title_hi' => 'अंगूर निर्यात का मौसम शुरू',
        'desc_hi' => 'नासिक के अंगूर अगले सप्ताह यूरोपीय बाजारों के लिए रवाना होंगे।',
        'content_hi' => 'निर्यातक इस साल अच्छी दरों को लेकर आशान्वित हैं क्योंकि प्रतिस्पर्धी देशों में उत्पादन कम है।',
        'title_mr' => 'द्राक्ष निर्यात हंगाम सुरू',
        'desc_mr' => 'नाशिकची द्राक्षे पुढील आठवड्यात युरोपीय बाजारपेठेत रवाना होणार.',
        'content_mr' => 'इतर देशांतील उत्पादन घटल्यामुळे यावर्षी चांगले दर मिळण्याची निर्यातदारांना आशा आहे.'
    ],
    [
        'id' => 18,
        'image' => 'https://akm-img-a-in.tosshub.com/indiatoday/images/story/202301/cold_wave-sixteen_nine.jpg?VersionId=_XjI2a45QyeTn.w_XyDG8SiqcYZWOGdL&size=690:388',
        'category' => 'weather',
        'date' => '2025-12-01',
        'source' => 'Meteo Group',
        'title_en' => 'Cold Wave Warning for North India',
        'desc_en' => 'Temperatures expected to drop below 5°C in Punjab and Haryana.',
        'content_en' => 'Farmers are advised to provide light irrigation to protect mustard and wheat crops from frost damage.',
        'title_hi' => 'उत्तर भारत के लिए शीत लहर की चेतावनी',
        'desc_hi' => 'पंजाब और हरियाणा में तापमान 5 डिग्री सेल्सियस से नीचे जाने की उम्मीद है।',
        'content_hi' => 'किसानों को सलाह दी जाती है कि वे फसलों को पाले से बचाने के लिए हल्की सिंचाई करें।',
        'title_mr' => 'उत्तर भारतासाठी थंडीच्या लाटेचा इशारा',
        'desc_mr' => 'पंजाब आणि हरियाणात तापमान ५ अंश सेल्सिअसच्या खाली जाण्याची शक्यता.',
        'content_mr' => 'थंडीपासून पिकांचे संरक्षण करण्यासाठी शेतकऱ्यांनी हलके पाणी देण्याचा सल्ला देण्यात आला आहे.'
    ],
    [
        'id' => 19,
        'image' => 'https://www.hindustantimes.com/ht-img/img/2024/02/21/550x309/The-council-of-ministers-cleared-an-FRP-of--340-pe_1708542196140.jpg',
        'category' => 'policy',
        'date' => '2025-11-23',
        'source' => 'Sugarcane Board',
        'title_en' => 'Sugarcane FRP Payments Cleared',
        'desc_en' => 'Mills have cleared 95% of Fair and Remunerative Price dues to farmers.',
        'content_en' => 'Strict government action has ensured timely payments to sugarcane growers for the previous season.',
        'title_hi' => 'गन्ना एफआरपी भुगतान मंजूर',
        'desc_hi' => 'मिलों ने किसानों को उचित और लाभकारी मूल्य का 95% बकाया चुका दिया है।',
        'content_hi' => 'सरकार की सख्त कार्रवाई ने गन्ना किसानों को समय पर भुगतान सुनिश्चित किया है।',
        'title_mr' => 'ऊसाची एफआरपी थकबाकी जमा',
        'desc_mr' => 'साखर कारखान्यांनी शेतकऱ्यांची ९५% एफआरपी थकबाकी जमा केली आहे.',
        'content_mr' => 'सरकारच्या कडक धोरणामुळे ऊस उत्पादकांना वेळेवर पैसे मिळणे शक्य झाले आहे.'
    ],
    [
        'id' => 20,
        'image' => 'https://www.deccanchronicle.com/h-upload/2025/02/10/1889363-tech.jpg',
        'category' => 'dairy',
        'date' => '2025-11-29',
        'source' => 'Vet News',
        'title_en' => 'Lumpy Skin Disease Vaccine Drive',
        'desc_en' => 'Nationwide vaccination drive launched to protect cattle livestock.',
        'content_en' => 'The government is providing free vaccines to prevent the spread of Lumpy Skin Disease in cows and buffaloes.',
        'title_hi' => 'लंपी त्वचा रोग टीकाकरण अभियान',
        'desc_hi' => 'पशुधन को बचाने के लिए राष्ट्रव्यापी टीकाकरण अभियान शुरू किया गया।',
        'content_hi' => 'सरकार गायों और भैंसों में लंपी त्वचा रोग को फैलने से रोकने के लिए मुफ्त टीके दे रही है।',
        'title_mr' => 'लंपी आजार लसीकरण मोहीम',
        'desc_mr' => 'जनावरांच्या संरक्षणासाठी देशव्यापी लसीकरण मोहीम राबवली जात आहे.',
        'content_mr' => 'गायी आणि म्हशींमध्ये लंपी आजाराचा प्रसार रोखण्यासाठी सरकार मोफत लस पुरवत आहे.'
    ],
    [
        'id' => 21,
        'image' => 'https://www.indiancooperative.com/wp-content/uploads/2022/12/nano-urea.jpeg',
        'category' => 'tech',
        'date' => '2025-11-27',
        'source' => 'Nano Labs',
        'title_en' => 'Nano Urea Production Scaled Up',
        'desc_en' => 'IFFCO announces three new plants to meet Nano Urea demand.',
        'content_en' => 'Nano Urea liquid is more efficient and cheaper than traditional granular urea, saving input costs for farmers.',
        'title_hi' => 'नैनो यूरिया का उत्पादन बढ़ाया गया',
        'desc_hi' => 'इफको ने नैनो यूरिया की मांग को पूरा करने के लिए तीन नए संयंत्रों की घोषणा की।',
        'content_hi' => 'नैनो यूरिया तरल पारंपरिक यूरिया की तुलना में अधिक कुशल और सस्ता है।',
        'title_mr' => 'नॅनो युरियाचे उत्पादन वाढवले',
        'desc_mr' => 'इफकोने नॅनो युरियाची मागणी पूर्ण करण्यासाठी तीन नवीन प्रकल्प जाहीर केले.',
        'content_mr' => 'नॅनो युरिया लिक्विड हे पारंपारिक युरियापेक्षा अधिक कार्यक्षम आणि स्वस्त आहे.'
    ],
    [
        'id' => 22,
        'image' => 'https://images.moneycontrol.com/static-mcnews/2019/07/Cardamom-770x433.jpg',
        'category' => 'crop',
        'date' => '2025-11-20',
        'source' => 'Spices Board',
        'title_en' => 'Cardamom Prices Hit Record High',
        'desc_en' => 'Shortage in production leads to price spike in Kerala markets.',
        'content_en' => 'Heavy rains in Idukki district damaged the crop, causing prices to soar to ₹3000 per kg.',
        'title_hi' => 'इलायची की कीमतें रिकॉर्ड ऊंचाई पर',
        'desc_hi' => 'उत्पादन में कमी से केरल के बाजारों में कीमतों में उछाल आया है।',
        'content_hi' => 'इडुक्की जिले में भारी बारिश ने फसल को नुकसान पहुंचाया, जिससे कीमतें बढ़ गईं।',
        'title_mr' => 'वेलचीचे भाव विक्रमी पातळीवर',
        'desc_mr' => 'उत्पादनात घट झाल्याने केरळच्या बाजारपेठेत भावात मोठी वाढ.',
        'content_mr' => 'इडुक्की जिल्ह्यात अतिवृष्टीमुळे पिकाचे नुकसान झाल्याने भाव गगनाला भिडले आहेत.'
    ],
    [
        'id' => 23,
        'image' => 'https://vertical.mt/wp-content/uploads/2023/08/Controlled-environment-agriculture-768x495.jpg',
        'category' => 'tech',
        'date' => '2025-11-25',
        'source' => 'Agri Startups',
        'title_en' => 'Vertical Farming Startup Gets Funding',
        'desc_en' => 'Bangalore-based startup raises funds to expand urban farming.',
        'content_en' => 'The startup uses hydroponics to grow exotic vegetables in city buildings, reducing transport costs.',
        'title_hi' => 'वर्टिकल फार्मिंग स्टार्टअप को फंडिंग मिली',
        'desc_hi' => 'बैंगलोर स्थित स्टार्टअप ने शहरी खेती का विस्तार करने के लिए धन जुटाया।',
        'content_hi' => 'स्टार्टअप शहर की इमारतों में विदेशी सब्जियां उगाने के लिए हाइड्रोपोनिक्स का उपयोग करता है।',
        'title_mr' => 'व्हर्टिकल फार्मिंग स्टार्टअपला फंडिंग मिळाले',
        'desc_mr' => 'बेंगळुरूस्थित स्टार्टअपने शहरी शेतीचा विस्तार करण्यासाठी निधी उभारला.',
        'content_mr' => 'हे स्टार्टअप वाहतूक खर्च कमी करण्यासाठी शहरातच हायड्रोपोनिक्सद्वारे भाज्या पिकवते.'
    ],
    [
        'id' => 24,
        'image' => 'https://tse1.mm.bing.net/th/id/OIP.HYha4NnuMQscuWPto6ikbAHaFj?pid=Api&P=0&h=220',
        'category' => 'policy',
        'date' => '2025-11-19',
        'source' => 'Energy Dept',
        'title_en' => 'Biogas Plant Setup Subsidy',
        'desc_en' => 'Govt offers 70% subsidy for setting up small biogas plants.',
        'content_en' => 'Farmers can generate free cooking gas and organic manure from cattle waste under this scheme.',
        'title_hi' => 'बायोगैस संयंत्र स्थापना सब्सिडी',
        'desc_hi' => 'सरकार छोटे बायोगैस संयंत्र स्थापित करने के लिए 70% सब्सिडी प्रदान करती है।',
        'content_hi' => 'किसान इस योजना के तहत मवेशियों के कचरे से मुफ्त रसोई गैस और खाद बना सकते हैं।',
        'title_mr' => 'बायोगॅस प्रकल्प उभारणी अनुदान',
        'desc_mr' => 'लहान बायोगॅस प्रकल्प उभारण्यासाठी सरकार ७०% अनुदान देत आहे.',
        'content_mr' => 'या योजनेद्वारे शेतकरी जनावरांच्या शेणापासून मोफत गॅस आणि सेंद्रिय खत मिळवू शकतात.'
    ],
    [
        'id' => 25,
        'image' => 'https://eng.ruralvoice.in/uploads/images/2021/04/image_750x_6073344a2409e.jpg',
        'category' => 'crop',
        'date' => '2025-11-28',
        'source' => 'Mushroom Society',
        'title_en' => 'Mushroom Cultivation Gains Traction',
        'desc_en' => 'Small farmers finding high profits in Oyster mushroom farming.',
        'content_en' => 'With low investment and high demand in hotels, mushroom farming is becoming a lucrative side business.',
        'title_hi' => 'मशरूम की खेती में तेजी',
        'desc_hi' => 'छोटे किसानों को ऑयस्टर मशरूम की खेती में भारी मुनाफा मिल रहा है।',
        'content_hi' => 'कम निवेश और होटलों में उच्च मांग के साथ, मशरूम की खेती एक आकर्षक व्यवसाय बन रही है।',
        'title_mr' => 'मशरूम शेतीला पसंती',
        'desc_mr' => 'लहान शेतकऱ्यांना ऑयस्टर मशरूमच्या शेतीतून मोठा नफा मिळत आहे.',
        'content_mr' => 'कमी गुंतवणूक आणि हॉटेल्समधील मोठी मागणी यामुळे मशरूम शेती फायदेशीर ठरत आहे.'
    ],
     [
        'id' => 26,
        'image' => 'https://bl-i.thgim.com/public/incoming/fx95u4/article66654362.ece/alternates/LANDSCAPE_1200/9865_18_9_2022_17_23_44_3_18_09_2022_TUT_03STORYPIX.JPG',
        'category' => 'policy',
        'date' => '2025-11-29',
        'source' => 'Insurance Co',
        'title_en' => 'Crop Insurance Claims Settled Fast',
        'desc_en' => 'New AI system settles Fasal Bima Yojana claims in 15 days.',
        'content_en' => 'The government has integrated satellite data to verify crop loss claims faster, ensuring quick relief.',
        'title_hi' => 'फसल बीमा दावों का निपटान तेजी से',
        'desc_hi' => 'नई एआई प्रणाली 15 दिनों में फसल बीमा योजना के दावों का निपटान करती है।',
        'content_hi' => 'सरकार ने फसल नुकसान के दावों को तेजी से सत्यापित करने के लिए उपग्रह डेटा को एकीकृत किया है।',
        'title_mr' => 'पीक विम्याचे दावे वेगाने निकाली',
        'desc_mr' => 'नवीन एआय प्रणाली १५ दिवसांत पीक विमा योजनेचे दावे निकाली काढते.',
        'content_mr' => 'नुकसानीची पडताळणी जलद करण्यासाठी सरकारने उपग्रह डेटाचा वापर सुरू केला आहे.'
    ],
    [
        'id' => 27,
        'image' => 'https://tfe-bd.sgp1.cdn.digitaloceanspaces.com/uploads/1544467835.jpg',
        'category' => 'crop',
        'date' => '2025-11-30',
        'source' => 'Vegetable Market',
        'title_en' => 'Cauliflower Glut Lowers Prices',
        'desc_en' => 'Oversupply of cauliflower in northern markets brings prices down to ₹5/kg.',
        'content_en' => 'While consumers are happy, farmers are struggling to recover transportation costs due to the price crash.',
        'title_hi' => 'फूलगोभी की अधिकता से कीमतें कम',
        'desc_hi' => 'उत्तरी बाजारों में फूलगोभी की अधिकता से कीमतें ₹5/किलो तक गिर गईं।',
        'content_hi' => 'जबकि उपभोक्ता खुश हैं, किसान कीमतों में गिरावट के कारण लागत वसूलने के लिए संघर्ष कर रहे हैं।',
        'title_mr' => 'फ्लॉवरच्या आवकेमुळे भाव गडगडले',
        'desc_mr' => 'उत्तर भारतात फ्लॉवरची आवक वाढल्याने भाव ₹५/किलोपर्यंत खाली आले.',
        'content_mr' => 'ग्राहक आनंदी असले तरी भाव पडल्याने शेतकऱ्यांना वाहतूक खर्च काढणेही कठीण झाले आहे.'
    ]
];

// --- LOGIC TO HANDLE SINGLE ARTICLE VIEW ---
$single_article = null;
if (isset($_GET['id'])) {
    $req_id = (int)$_GET['id'];
    foreach ($news_articles as $a) {
        if ($a['id'] === $req_id) {
            $single_article = $a;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? 'light-mode' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($lang['news_page_title']) ? $lang['news_page_title'] : 'KisanX News'; ?></title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* --- News Page Specific CSS --- */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
            padding-bottom: 3rem;
        }
        
        /* Animation Keyframes */
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .news-card {
            background: var(--kd-bg-surface);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--glass-border);
            position: relative;
            height: 100%; 
            /* Base state for animation */
            opacity: 0; 
            animation: slideUpFade 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        html.light-mode .news-card {
            background: #fff;
            border-color: rgba(0,0,0,0.05);
        }
        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
            border-color: var(--kd-earthy-green);
        }
        .news-image-container {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        .news-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .news-card:hover .news-image-container img {
            transform: scale(1.1);
        }
        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            z-index: 2;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .cat-tech { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .cat-price { background: linear-gradient(135deg, #10b981, #059669); }
        .cat-policy { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .cat-weather { background: linear-gradient(135deg, #6b7280, #4b5563); }
        .cat-crop { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .cat-dairy { background: linear-gradient(135deg, #ec4899, #db2777); }
        
        .news-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .news-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--kd-muted);
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .news-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            color: var(--kd-text);
            line-height: 1.4;
            font-family: 'Montserrat', sans-serif;
        }
        .news-desc {
            font-size: 0.95rem;
            color: var(--kd-muted);
            margin-bottom: 1.5rem;
            flex-grow: 1;
            line-height: 1.6;
        }
        .news-footer {
            border-top: 1px solid var(--glass-border);
            padding-top: 1rem;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .source-credit {
            font-size: 0.85rem;
            font-style: italic;
            color: var(--kd-warm-gold);
            font-weight: 600;
        }
        .read-more-btn {
            text-decoration: none;
            color: var(--kd-earthy-green);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            padding: 5px 10px;
            border-radius: 6px;
            background: rgba(104, 211, 145, 0.1);
        }
        .read-more-btn:hover {
            background: var(--kd-earthy-green);
            color: #fff;
        }
        .hero-news {
            background: linear-gradient(rgba(18, 24, 27, 0.8), rgba(18, 24, 27, 0.9)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            text-align: center;
            padding: 4rem 2rem;
            border-radius: 20px;
            margin-top: 2rem;
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
            animation: fadeIn 1s ease;
        }
        .hero-news h2 {
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 1rem;
            font-family: 'Montserrat', sans-serif;
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .hero-news p {
            color: #e2e8f0;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .article-container {
            max-width: 900px;
            margin: 3rem auto;
            background: var(--kd-bg-surface);
            padding: 0;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--glass-border);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        html.light-mode .article-container { background: #fff; }
        .article-header-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .article-body { padding: 3rem; }
        .article-badges { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .article-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--kd-text);
            margin-bottom: 1rem;
            font-family: 'Montserrat', sans-serif;
            line-height: 1.2;
        }
        .article-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            color: var(--kd-muted);
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--glass-border);
            font-size: 0.95rem;
        }
        .article-text {
            color: var(--kd-text);
            font-size: 1.15rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--kd-bg);
            color: var(--kd-muted);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid var(--glass-border);
        }
        .back-btn:hover {
            background: var(--kd-earthy-green);
            color: #fff;
            transform: translateX(-5px);
        }
        @media (max-width: 768px) {
            .hero-news h2 { font-size: 2rem; }
            .article-title { font-size: 1.8rem; }
            .article-header-img { height: 250px; }
            .article-body { padding: 1.5rem; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        
        <?php if(isset($_SESSION['user'])): ?>
            <div style="margin-top: 1rem; margin-bottom: 1rem;">
                <a href="<?php echo $dashboardLink; ?>" style="display: inline-flex; align-items: center; gap: 8px; color: var(--kd-earthy-green); text-decoration: none; font-weight: 600; font-size: 0.95rem;">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>

        <?php if ($single_article): ?>
            <?php 
                // Resolve language for Single View
                $tKey = 'title_' . $current_lang;
                $cKey = 'content_' . $current_lang;
                
                // Fallback to English if current lang key missing
                $title = !empty($single_article[$tKey]) ? $single_article[$tKey] : $single_article['title_en'];
                $content = !empty($single_article[$cKey]) ? $single_article[$cKey] : $single_article['content_en'];
                
                $cat_class = 'cat-' . $single_article['category'];
                $cat_label = ucfirst($single_article['category']);
            ?>

            <div class="article-container">
                <img src="<?php echo htmlspecialchars($single_article['image']); ?>" alt="News Cover" class="article-header-img">
                
                <div class="article-body">
                    <div class="article-badges">
                        <span class="category-badge <?php echo $cat_class; ?>" style="position:static;">
                            <?php echo $cat_label; ?>
                        </span>
                    </div>

                    <h1 class="article-title"><?php echo htmlspecialchars($title); ?></h1>

                    <div class="article-info">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d M Y, l', strtotime($single_article['date'])); ?></span>
                        <span><i class="fas fa-newspaper"></i> Source: <strong><?php echo htmlspecialchars($single_article['source']); ?></strong></span>
                    </div>

                    <div class="article-text">
                        <p><?php echo nl2br(htmlspecialchars($content)); ?></p>
                    </div>

                    <a href="news.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to News
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="hero-news">
                <h2><?php echo isset($lang['news_hero_title']) ? $lang['news_hero_title'] : 'Latest Agriculture News'; ?> 📰</h2>
                <p><?php echo isset($lang['news_hero_subtitle']) ? $lang['news_hero_subtitle'] : 'Stay updated with the latest trends in farming, market prices, and technology.'; ?></p>
            </div>

            <div class="news-grid">
                <?php foreach ($news_articles as $index => $item): ?>
                    <?php 
                        // Resolve language for Grid View
                        $tKey = 'title_' . $current_lang;
                        $dKey = 'desc_' . $current_lang;
                        
                        $title = !empty($item[$tKey]) ? $item[$tKey] : $item['title_en'];
                        $desc  = !empty($item[$dKey]) ? $item[$dKey] : $item['desc_en'];
                        
                        $cat_key = 'news_category_' . $item['category'];
                        $cat_label = isset($lang[$cat_key]) ? $lang[$cat_key] : ucfirst($item['category']);
                        $cat_class = 'cat-' . $item['category'];

                        // Calculate Stagger Delay (max 10 items stagger to prevent long waits on huge lists)
                        $delay = min($index * 0.1, 1.0); 
                    ?>
                    
                    <article class="news-card" style="animation-delay: <?php echo $delay; ?>s;">
                        <div class="news-image-container">
                            <span class="category-badge <?php echo $cat_class; ?>">
                                <?php echo $cat_label; ?>
                            </span>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="News Image">
                        </div>
                        
                        <div class="news-content">
                            <div class="news-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($item['date'])); ?></span>
                            </div>

                            <h3 class="news-title"><?php echo htmlspecialchars($title); ?></h3>
                            <p class="news-desc"><?php echo htmlspecialchars($desc); ?></p>

                            <div class="news-footer">
                                <span class="source-credit">
                                    <?php echo isset($lang['news_source']) ? $lang['news_source'] : 'Source:'; ?> 
                                    <?php echo htmlspecialchars($item['source']); ?>
                                </span>
                                <a href="news.php?id=<?php echo $item['id']; ?>" class="read-more-btn">
                                    <?php echo isset($lang['news_read_more']) ? $lang['news_read_more'] : 'Read More'; ?> 
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'footer.php'; ?>

</body>
</html>