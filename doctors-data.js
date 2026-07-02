// Mock doctors database
const doctorsData = [
  {
    id: 1,
    name: "Dr. Priya Sharma",
    specialty: "Cardiologist",
    experience: 15,
    qualification: "MBBS, MD (Cardiology), FACC",
    rating: 4.7,
    photo:
      "https://plus.unsplash.com/premium_photo-1682089872205-dbbae3e4ba32?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 123-4567",
    email: "priya.sharma@drmap.com",
    whatsapp: "+15551234567",
    addresses: [
      "City Hospital, 123 Main Street, New York, NY 10001",
      "Heart Care Clinic, 456 Park Avenue, New York, NY 10022",
    ],
    quickFacts: [
      { label: "Languages", value: "English, Hindi" },
      { label: "Consultation Fee", value: "$150" },
      {
        label: "Special Interests",
        value: "Preventive Cardiology, Heart Failure",
      },
    ],
    locations: [
      {
        address: "City Hospital, 123 Main Street, New York, NY 10001",
        lat: 40.7128,
        lng: -74.006,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=40.7128,-74.0060&output=embed",
      },
      {
        address: "Heart Care Clinic, 456 Park Avenue, New York, NY 10022",
        lat: 40.7648,
        lng: -73.973,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=40.7648,-73.9730&output=embed",
      },
    ],
    timing: "Mon-Fri: 9:00 AM - 5:00 PM, Sat: 10:00 AM - 2:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/sarahjohnson",
      twitter: "https://twitter.com/drsarahjohnson",
      facebook: "https://facebook.com/drsarahjohnson",
    },
    speech:
      "I believe in providing compassionate, patient-centered cardiac care. My goal is to help you achieve optimal heart health through evidence-based medicine and personalized treatment plans.",
    about:
      "Dr. Sarah Johnson is a board-certified cardiologist with over 15 years of experience in treating cardiovascular diseases. She completed her medical degree at Harvard Medical School and her cardiology fellowship at Johns Hopkins Hospital. Dr. Johnson specializes in preventive cardiology, heart failure management, and interventional procedures. She has published numerous research papers in prestigious medical journals and is actively involved in clinical trials for innovative cardiac treatments. Her dedication to patient care and medical excellence has earned her recognition as one of the top cardiologists in the region.",
    videos: [
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
    ],
    gallery: [
      "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1581595220892-b0739db3ba8c?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "John Smith",
        rating: 5,
        comment:
          "Dr. Johnson is exceptional! She took the time to explain everything and made me feel comfortable throughout my treatment.",
        date: "2024-11-15",
      },
      {
        name: "Emily Davis",
        rating: 5,
        comment:
          "Highly professional and caring. I'm grateful for her expertise in managing my heart condition.",
        date: "2024-10-28",
      },
      {
        name: "Michael Brown",
        rating: 4,
        comment:
          "Very knowledgeable doctor. Wait times can be long but worth it.",
        date: "2024-10-10",
      },
    ],
  },
  {
    id: 2,
    name: "Dr. Arjun Mehra",
    specialty: "Orthopedic Surgeon",
    experience: 12,
    qualification: "MBBS, MS (Orthopedics), FAAOS",
    rating: 5.0,
    photo:
      "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400&h=400&fit=crop",
    phone: "+1 (555) 234-5678",
    email: "michael.chen@medcare.com",
    whatsapp: "+15552345678",
    addresses: [
      "Orthopedic Center, 789 Medical Plaza, Los Angeles, CA 90001",
      "Sports Medicine Clinic, 321 Athletic Drive, Los Angeles, CA 90015",
    ],
    quickFacts: [
      { label: "Languages", value: "English, Spanish" },
      { label: "Consultation Fee", value: "$140" },
      {
        label: "Special Interests",
        value: "Joint Replacement, Sports Injuries",
      },
    ],
    locations: [
      {
        address: "Orthopedic Center, 789 Medical Plaza, Los Angeles, CA 90001",
        lat: 34.0522,
        lng: -118.2437,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=34.0522,-118.2437&output=embed",
      },
      {
        address:
          "Sports Medicine Clinic, 321 Athletic Drive, Los Angeles, CA 90015",
        lat: 34.0407,
        lng: -118.2468,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=34.0407,-118.2468&output=embed",
      },
    ],
    timing: "Mon-Thu: 8:00 AM - 6:00 PM, Fri: 8:00 AM - 4:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/michaelchen",
      twitter: "https://twitter.com/drmichaelchen",
      facebook: "https://facebook.com/drmichaelchen",
    },
    speech:
      "As an orthopedic surgeon, I'm committed to helping you regain mobility and live pain-free. Whether it's sports injuries or joint replacements, I'm here to guide you through your recovery journey.",
    about:
      "Dr. Arjun Mehra is a renowned orthopedic surgeon specializing in joint replacement surgery, sports medicine, and trauma care. He earned his medical degree from Stanford University School of Medicine and completed his orthopedic surgery residency at Mayo Clinic. With 12 years of experience, Dr. Chen has performed over 2,000 successful surgeries, including complex joint reconstructions and minimally invasive procedures. He has a special interest in treating sports-related injuries and works closely with professional athletes. Dr. Chen is known for his meticulous surgical techniques and comprehensive rehabilitation programs that help patients achieve optimal recovery outcomes.",
    videos: [
      "https://youtu.be/30Wev9UqnfY?si=TQJZ_YaPHsXBhCpk",
      "https://youtu.be/zxzBBLP7f0k?si=XYO7x792DkfGbjRX",
      "https://youtu.be/p3z9FLYijrQ?si=0a3j_h4eCZv3sbIA",
      "https://youtu.be/Ax28Vt8HAJI?si=5z6kPE7Id443FI5V",
      "https://youtu.be/hSfd-mEjW4I?si=kRH7_wwDpMLJZYyM",
    ],
    gallery: [
      "https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1516549655169-df83a0774514?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1581594549595-35f6edc7b762?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Robert Wilson",
        rating: 5,
        comment:
          "Dr. Chen performed my knee replacement surgery. I'm back to hiking within 3 months. Amazing surgeon!",
        date: "2024-11-20",
      },
      {
        name: "Lisa Anderson",
        rating: 5,
        comment:
          "Professional, skilled, and compassionate. Highly recommend for any orthopedic issues.",
        date: "2024-11-05",
      },
      {
        name: "David Martinez",
        rating: 5,
        comment:
          "Fixed my shoulder injury perfectly. I can play tennis again without pain!",
        date: "2024-10-15",
      },
    ],
  },
  {
    id: 3,
    name: "Dr. Neha Singh",
    specialty: "Pediatrician",
    experience: 10,
    qualification: "MBBS, MD (Pediatrics), FAAP",
    rating: 5.0,
    photo:
      "https://images.unsplash.com/photo-1659353888906-adb3e0041693?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 345-6789",
    email: "emily.rodriguez@medcare.com",
    whatsapp: "+15553456789",
    addresses: [
      "Children's Health Center, 555 Rainbow Road, Chicago, IL 60601",
      "Pediatric Care Clinic, 888 Kids Avenue, Chicago, IL 60610",
    ],
    quickFacts: [
      { label: "Languages", value: "English" },
      { label: "Consultation Fee", value: "$90" },
      { label: "Special Interests", value: "Child Development, Nutrition" },
    ],
    locations: [
      {
        address:
          "Children's Health Center, 555 Rainbow Road, Chicago, IL 60601",
        lat: 41.8781,
        lng: -87.6298,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=41.8781,-87.6298&output=embed",
      },
      {
        address: "Pediatric Care Clinic, 888 Kids Avenue, Chicago, IL 60610",
        lat: 41.9076,
        lng: -87.6315,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=41.9076,-87.6315&output=embed",
      },
    ],
    timing: "Mon-Fri: 8:30 AM - 5:30 PM, Sat: 9:00 AM - 1:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/emilyrodriguez",
      twitter: "https://twitter.com/dremilyrodriguez",
      facebook: "https://facebook.com/dremilyrodriguez",
    },
    speech:
      "Every child deserves the best start in life. I'm passionate about providing comprehensive pediatric care that supports your child's growth, development, and overall well-being in a warm and friendly environment.",
    about:
      "Dr. Emily Rodriguez is a dedicated pediatrician with a decade of experience in child healthcare. She graduated from University of Chicago Medical School and completed her pediatric residency at Boston Children's Hospital. Dr. Rodriguez specializes in preventive care, childhood nutrition, developmental assessments, and managing chronic pediatric conditions. She is particularly skilled in creating a comfortable environment for children during medical visits, making even the most anxious young patients feel at ease. Dr. Rodriguez stays current with the latest pediatric research and is an advocate for childhood vaccination and wellness programs. Parents appreciate her thorough explanations and her genuine care for each child's unique needs.",
    videos: [
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "https://youtu.be/gVQETWmgO4A?si=utqNBuWwahJH5xgG",
    ],
    gallery: [
      "https://i.pinimg.com/1200x/7f/bb/0f/7fbb0f1e63b647d34b710cc70b726381.jpg",
      "https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1581594693702-fbdc51b2763b?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Jennifer Lee",
        rating: 5,
        comment:
          "Dr. Rodriguez is wonderful with kids! My daughter actually looks forward to her checkups now.",
        date: "2024-11-18",
      },
      {
        name: "Thomas Garcia",
        rating: 5,
        comment:
          "Very knowledgeable and patient. She answered all our questions about our newborn's care.",
        date: "2024-11-01",
      },
      {
        name: "Amanda White",
        rating: 5,
        comment:
          "The best pediatrician we've ever had. Truly cares about her patients.",
        date: "2024-10-20",
      },
    ],
  },
  {
    id: 4,
    name: "Dr. Rohan Patel",
    specialty: "Dermatologist",
    experience: 8,
    qualification: "MBBS, MD (Dermatology), FAAD",
    rating: 4.7,
    photo:
      "https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=400&h=400&fit=crop",
    phone: "+1 (555) 456-7890",
    email: "james.taylor@medcare.com",
    whatsapp: "+15554567890",
    addresses: [
      "Skin Care Institute, 777 Beauty Boulevard, Miami, FL 33101",
      "Dermatology Associates, 999 Clear Skin Lane, Miami, FL 33130",
    ],
    quickFacts: [
      { label: "Languages", value: "English, Spanish" },
      { label: "Consultation Fee", value: "$130" },
      { label: "Special Interests", value: "Medical & Cosmetic Dermatology" },
    ],
    locations: [
      {
        address: "Skin Care Institute, 777 Beauty Boulevard, Miami, FL 33101",
        lat: 25.7617,
        lng: -80.1918,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=25.7617,-80.1918&output=embed",
      },
      {
        address: "Dermatology Associates, 999 Clear Skin Lane, Miami, FL 33130",
        lat: 25.784,
        lng: -80.196,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=25.7840,-80.1960&output=embed",
      },
    ],
    timing: "Tue-Sat: 10:00 AM - 6:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/jamestaylor",
      twitter: "https://twitter.com/drjamestaylor",
      facebook: "https://facebook.com/drjamestaylor",
    },
    speech:
      "Healthy skin is a reflection of overall wellness. I combine medical expertise with aesthetic sensibility to help you achieve and maintain beautiful, healthy skin at any age.",
    about:
      "Dr. James Taylor is a board-certified dermatologist with 8 years of experience in both medical and cosmetic dermatology. He received his medical degree from University of Miami and completed his dermatology residency at NYU Langone Medical Center. Dr. Taylor specializes in treating acne, eczema, psoriasis, skin cancer screening, and anti-aging treatments. He is proficient in advanced procedures including laser therapy, chemical peels, Botox, and dermal fillers. His approach combines evidence-based medicine with personalized care, ensuring each patient receives treatments tailored to their specific skin type and concerns. Dr. Taylor is passionate about educating patients on proper skincare routines and sun protection.",
    videos: [
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "https://youtu.be/gVQETWmgO4A?si=utqNBuWwahJH5xgG",
    ],
    gallery: [
      "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Sophia Martinez",
        rating: 5,
        comment:
          "Dr. Taylor cleared up my acne completely! His treatment plan was effective and gentle.",
        date: "2024-11-12",
      },
      {
        name: "Christopher Lee",
        rating: 5,
        comment:
          "Professional and knowledgeable. Great results with my skin concerns.",
        date: "2024-10-30",
      },
      {
        name: "Olivia Brown",
        rating: 4,
        comment: "Very good dermatologist. Clinic is clean and modern.",
        date: "2024-10-08",
      },
    ],
  },
  {
    id: 5,
    name: "Dr. Aisha Khan",
    specialty: "Gynecologist",
    experience: 14,
    qualification: "MBBS, MD (OB/GYN), FACOG",
    rating: 5.0,
    photo:
      "https://plus.unsplash.com/premium_photo-1664475543697-229156438e1e?q=80&w=686&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 567-8901",
    email: "aisha.patel@medcare.com",
    whatsapp: "+15555678901",
    addresses: [
      "Women's Health Center, 234 Wellness Way, Houston, TX 77001",
      "OB/GYN Specialists, 567 Care Court, Houston, TX 77010",
    ],
    quickFacts: [
      { label: "Languages", value: "English" },
      { label: "Consultation Fee", value: "$160" },
      {
        label: "Special Interests",
        value: "High-risk Pregnancy, Minimally Invasive Surgery",
      },
    ],
    locations: [
      {
        address: "Women's Health Center, 234 Wellness Way, Houston, TX 77001",
        lat: 29.7604,
        lng: -95.3698,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=29.7604,-95.3698&output=embed",
      },
      {
        address: "OB/GYN Specialists, 567 Care Court, Houston, TX 77010",
        lat: 29.744,
        lng: -95.3639,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=29.7440,-95.3639&output=embed",
      },
    ],
    timing: "Mon-Fri: 9:00 AM - 5:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/aishapatel",
      twitter: "https://twitter.com/draishapatel",
      facebook: "https://facebook.com/draishapatel",
    },
    speech:
      "Women's health is my passion. I provide comprehensive, compassionate care for all stages of a woman's life, from adolescence through menopause and beyond, in a comfortable and supportive environment.",
    about:
      "Dr. Aisha Patel is a highly experienced gynecologist and obstetrician with 14 years of dedicated service in women's health. She completed her medical education at Baylor College of Medicine and her residency in obstetrics and gynecology at Cleveland Clinic. Dr. Patel specializes in high-risk pregnancies, minimally invasive gynecologic surgery, family planning, and menopause management. She has delivered over 3,000 babies and performed countless successful surgical procedures. Dr. Patel is known for her empathetic approach and takes time to ensure her patients understand all their options. She is actively involved in community health initiatives focused on women's wellness and education.",
    videos: ["https://www.youtube.com/embed/dQw4w9WgXcQ"],
    gallery: [
      "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1584982751601-97dcc096659c?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Rachel Johnson",
        rating: 5,
        comment:
          "Dr. Patel delivered both my children. She's caring, professional, and makes you feel heard.",
        date: "2024-11-25",
      },
      {
        name: "Maria Santos",
        rating: 5,
        comment:
          "Excellent gynecologist. Very thorough and takes time to explain everything.",
        date: "2024-11-10",
      },
      {
        name: "Sarah Thompson",
        rating: 5,
        comment: "I trust Dr. Patel completely with my health. She's the best!",
        date: "2024-10-25",
      },
    ],
  },
  {
    id: 6,
    name: "Dr. Vikram Desai",
    specialty: "Neurologist",
    experience: 18,
    qualification: "MBBS, MD (Neurology), FAAN",
    rating: 5.0,
    photo:
      "https://images.unsplash.com/photo-1659353887977-c310d90c751a?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 678-9012",
    email: "robert.anderson@medcare.com",
    whatsapp: "+15556789012",
    addresses: [
      "Neurology Institute, 890 Brain Avenue, Boston, MA 02101",
      "Neurological Care Center, 345 Mind Street, Boston, MA 02110",
    ],
    quickFacts: [
      { label: "Languages", value: "English" },
      { label: "Consultation Fee", value: "$180" },
      {
        label: "Special Interests",
        value: "Stroke, Epilepsy, Neurodegenerative Disorders",
      },
    ],
    locations: [
      {
        address: "Neurology Institute, 890 Brain Avenue, Boston, MA 02101",
        lat: 42.3601,
        lng: -71.0589,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=42.3601,-71.0589&output=embed",
      },
      {
        address: "Neurological Care Center, 345 Mind Street, Boston, MA 02110",
        lat: 42.359,
        lng: -71.0595,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=42.3590,-71.0595&output=embed",
      },
    ],
    timing: "Mon-Thu: 8:00 AM - 4:00 PM, Fri: 8:00 AM - 12:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/robertanderson",
      twitter: "https://twitter.com/drrobertanderson",
      facebook: "https://facebook.com/drrobertanderson",
    },
    speech:
      "The brain is incredibly complex, but understanding neurological conditions shouldn't be. I'm dedicated to providing clear explanations and effective treatments for all neurological disorders.",
    about:
      "Dr. Robert Anderson is a distinguished neurologist with 18 years of experience in diagnosing and treating complex neurological conditions. He earned his medical degree from Johns Hopkins University and completed his neurology fellowship at Massachusetts General Hospital. Dr. Anderson specializes in treating stroke, epilepsy, multiple sclerosis, Parkinson's disease, and chronic headaches. He has conducted extensive research in neurodegenerative diseases and has published over 50 peer-reviewed articles. Dr. Anderson utilizes the latest diagnostic technologies including advanced neuroimaging and electrophysiology. His patients appreciate his thorough diagnostic approach and his ability to explain complex medical conditions in understandable terms.",
    videos: [
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
    ],
    gallery: [
      "https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1516549655169-df83a0774514?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "William Harris",
        rating: 5,
        comment:
          "Dr. Anderson correctly diagnosed my condition after years of seeing other doctors. Life-changing!",
        date: "2024-11-22",
      },
      {
        name: "Patricia Clark",
        rating: 5,
        comment:
          "Incredibly knowledgeable and patient. He takes time to answer all questions.",
        date: "2024-11-08",
      },
      {
        name: "George Miller",
        rating: 5,
        comment:
          "Managing my epilepsy has been so much better under Dr. Anderson's care.",
        date: "2024-10-18",
      },
    ],
  },
  {
    id: 7,
    name: "Dr. Meera Nair",
    specialty: "Pulmonologist",
    experience: 11,
    qualification: "MBBS, MD (Pulmonology), FCCP",
    rating: 5.0,
    photo:
      "https://images.unsplash.com/photo-1741707039571-f1c3f957a2e8?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 789-0123",
    email: "lisa.wong@medcare.com",
    whatsapp: "+15557890123",
    addresses: [
      "Respiratory Care Center, 111 Lung Lane, Seattle, WA 98101",
      "Pulmonary Specialists Clinic, 222 Breath Boulevard, Seattle, WA 98104",
    ],
    quickFacts: [
      { label: "Languages", value: "English" },
      { label: "Consultation Fee", value: "$145" },
      { label: "Special Interests", value: "Asthma, COPD, Sleep Apnea" },
    ],
    locations: [
      {
        address: "Respiratory Care Center, 111 Lung Lane, Seattle, WA 98101",
        lat: 47.6062,
        lng: -122.3321,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=47.6062,-122.3321&output=embed",
      },
      {
        address:
          "Pulmonary Specialists Clinic, 222 Breath Boulevard, Seattle, WA 98104",
        lat: 47.6097,
        lng: -122.3331,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=47.6097,-122.3331&output=embed",
      },
    ],
    timing: "Mon-Fri: 9:00 AM - 5:00 PM, Sat: 10:00 AM - 2:00 PM",
    social: {
      linkedin: "https://linkedin.com/in/lisawong",
      twitter: "https://twitter.com/drlisawong",
      facebook: "https://facebook.com/drlisawong",
    },
    speech:
      "Respiratory health is fundamental to overall wellness. I'm committed to helping patients with lung and breathing disorders achieve better quality of life through comprehensive care and treatment.",
    about:
      "Dr. Lisa Wong is an accomplished pulmonologist with 11 years of experience in diagnosing and treating respiratory diseases. She graduated from University of Washington School of Medicine and completed her pulmonology fellowship at Stanford University. Dr. Wong specializes in asthma management, COPD treatment, lung cancer screening, sleep apnea, and interstitial lung disease. She is proficient in performing bronchoscopy, pulmonary function tests, and other advanced diagnostic procedures. Dr. Wong is passionate about patient education and prevention strategies for respiratory health. She has helped thousands of patients breathe easier and improve their quality of life through personalized treatment plans.",
    videos: [
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "https://www.youtube.com/embed/dQw4w9WgXcQ",
    ],
    gallery: [
      "https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Charles White",
        rating: 5,
        comment:
          "Dr. Wong's treatment completely changed how I manage my asthma. I can exercise again!",
        date: "2024-11-20",
      },
      {
        name: "Diana Green",
        rating: 5,
        comment:
          "Very thorough and explains everything clearly. Best pulmonologist I've seen.",
        date: "2024-11-02",
      },
      {
        name: "Edward Brown",
        rating: 5,
        comment:
          "Professional and caring. My breathing has improved significantly under her care.",
        date: "2024-10-16",
      },
    ],
  },
  {
    id: 8,
    name: "Dr. Sanjay Rao",
    specialty: "Urologist",
    experience: 13,
    qualification: "MBBS, MS (Urology), FACS",
    rating: 5.0,
    photo:
      "https://images.unsplash.com/photo-1581125119293-4803aa54b372?q=80&w=1112&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
    phone: "+1 (555) 890-1234",
    email: "marcus.thompson@medcare.com",
    whatsapp: "+15558901234",
    addresses: [
      "Urology Associates, 333 Health Plaza, Denver, CO 80202",
      "Advanced Urology Center, 444 Medical Drive, Denver, CO 80210",
    ],
    quickFacts: [
      { label: "Languages", value: "English" },
      { label: "Consultation Fee", value: "$150" },
      {
        label: "Special Interests",
        value: "Kidney Stones, Minimally Invasive Urology",
      },
    ],
    locations: [
      {
        address: "Urology Associates, 333 Health Plaza, Denver, CO 80202",
        lat: 39.7392,
        lng: -104.9903,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=39.7392,-104.9903&output=embed",
      },
      {
        address: "Advanced Urology Center, 444 Medical Drive, Denver, CO 80210",
        lat: 39.745,
        lng: -104.989,
        mapEmbedUrl:
          "https://maps.google.com/maps?q=39.7450,-104.9890&output=embed",
      },
    ],
    timing: "Mon-Fri: 8:30 AM - 5:30 PM",
    social: {
      linkedin: "https://linkedin.com/in/marcusthompson",
      twitter: "https://twitter.com/drmarcusthompson",
      facebook: "https://facebook.com/drmarcusthompson",
    },
    speech:
      "Urological health matters, and I'm here to provide compassionate, expert care for all conditions affecting the urinary and reproductive systems. Your comfort and confidence are my priorities.",
    about:
      "Dr. Marcus Thompson is a highly skilled urologist with 13 years of clinical experience treating a wide range of urological conditions. He earned his medical degree from University of Colorado School of Medicine and completed his urology residency at Duke University Medical Center. Dr. Thompson specializes in treating kidney stones, urinary incontinence, benign prostatic hyperplasia, prostate cancer, erectile dysfunction, and infertility issues. He is experienced in both open and minimally invasive surgical techniques including laparoscopy and robotic-assisted surgery. Dr. Thompson is known for his compassionate approach and his ability to discuss sensitive health matters in a professional and comfortable manner. He has successfully treated over 5,000 patients with excellent outcomes.",
    videos: ["https://www.youtube.com/embed/dQw4w9WgXcQ"],
    gallery: [
      "https://images.unsplash.com/photo-1551601651-2a8555f1a136?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1516549655169-df83a0774514?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=400&h=300&fit=crop",
      "https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=400&h=300&fit=crop",
    ],
    reviews: [
      {
        name: "Kevin Lewis",
        rating: 5,
        comment:
          "Dr. Thompson resolved my kidney stone issue with minimal pain. Excellent surgeon!",
        date: "2024-11-19",
      },
      {
        name: "Sandra Miller",
        rating: 5,
        comment:
          "Very professional and knowledgeable. Made me feel comfortable discussing my concerns.",
        date: "2024-11-03",
      },
      {
        name: "Brian Davis",
        rating: 5,
        comment:
          "Outstanding care and expertise. I recommend him to all my friends.",
        date: "2024-10-22",
      },
    ],
  },
];

// Function to get all doctors
function getAllDoctors() {
  return doctorsData;
}

// Function to get doctor by ID
function getDoctorById(id) {
  return doctorsData.find((doctor) => doctor.id === parseInt(id));
}

// Function to get doctors by specialty
function getDoctorsBySpecialty(specialty) {
  return doctorsData.filter(
    (doctor) => doctor.specialty.toLowerCase() === specialty.toLowerCase()
  );
}
