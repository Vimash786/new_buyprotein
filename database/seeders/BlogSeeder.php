<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\BlogLike;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create blogs directory if it doesn't exist
        if (!Storage::disk('public')->exists('blogs')) {
            Storage::disk('public')->makeDirectory('blogs');
        }

        // Create sample blogs
        $blogs = [
            [
                'title' => 'The Ultimate Guide to Protein Supplements',
                'slug' => 'ultimate-guide-protein-supplements',
                'excerpt' => 'Everything you need to know about protein supplements, from whey to casein and plant-based options.',
                'content' => $this->getProteinSupplementContent(),
                'featured_image' => 'blogs/protein-supplements.jpg',
                'meta_title' => 'Complete Guide to Protein Supplements - BuyProtein',
                'meta_description' => 'Discover the best protein supplements for your fitness goals. Learn about whey, casein, plant-based proteins and more.',
                'tags' => ['protein', 'supplements', 'fitness', 'muscle building', 'nutrition'],
                'is_featured' => true,
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'views_count' => 2500,
                'likes_count' => 180,
                'comments_count' => 25,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Pre-Workout vs Post-Workout Nutrition',
                'slug' => 'pre-workout-vs-post-workout-nutrition',
                'excerpt' => 'Understanding the importance of timing your nutrition around your workouts for optimal performance and recovery.',
                'content' => $this->getWorkoutNutritionContent(),
                'featured_image' => 'blogs/workout-nutrition.jpg',
                'meta_title' => 'Pre vs Post Workout Nutrition - When to Eat for Best Results',
                'meta_description' => 'Learn when and what to eat before and after your workouts to maximize performance and recovery.',
                'tags' => ['nutrition', 'workout', 'pre-workout', 'post-workout', 'performance'],
                'is_featured' => true,
                'status' => 'published',
                'published_at' => now()->subDays(14),
                'views_count' => 1800,
                'likes_count' => 145,
                'comments_count' => 18,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Building Muscle: The Science Behind Protein Synthesis',
                'slug' => 'building-muscle-protein-synthesis-science',
                'excerpt' => 'Dive deep into the science of muscle protein synthesis and how to optimize it for maximum muscle growth.',
                'content' => $this->getProteinSynthesisContent(),
                'featured_image' => 'blogs/muscle-protein-synthesis.jpg',
                'meta_title' => 'Muscle Protein Synthesis - The Science of Building Muscle',
                'meta_description' => 'Learn the science behind muscle protein synthesis and how to optimize it for muscle growth.',
                'tags' => ['muscle building', 'protein synthesis', 'science', 'fitness', 'bodybuilding'],
                'is_featured' => false,
                'status' => 'published',
                'published_at' => now()->subDays(21),
                'views_count' => 1200,
                'likes_count' => 95,
                'comments_count' => 12,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Plant-Based Protein Sources for Vegetarians',
                'slug' => 'plant-based-protein-sources-vegetarians',
                'excerpt' => 'Complete guide to plant-based protein sources that provide all essential amino acids for vegetarian athletes.',
                'content' => $this->getPlantProteinContent(),
                'featured_image' => 'blogs/plant-protein.jpg',
                'meta_title' => 'Best Plant-Based Protein Sources for Vegetarians',
                'meta_description' => 'Discover the best plant-based protein sources for vegetarian athletes and fitness enthusiasts.',
                'tags' => ['plant protein', 'vegetarian', 'vegan', 'amino acids', 'nutrition'],
                'is_featured' => false,
                'status' => 'published',
                'published_at' => now()->subDays(28),
                'views_count' => 950,
                'likes_count' => 72,
                'comments_count' => 8,
                'author_id' => 1,
                'category_id' => 1,
            ],
            [
                'title' => 'Common Supplement Myths Debunked',
                'slug' => 'common-supplement-myths-debunked',
                'excerpt' => 'Separating fact from fiction - we debunk the most common myths about fitness supplements.',
                'content' => $this->getSupplementMythsContent(),
                'featured_image' => 'blogs/supplement-myths.jpg',
                'meta_title' => 'Fitness Supplement Myths Debunked - The Truth About Supplements',
                'meta_description' => 'Learn the truth about common supplement myths and make informed decisions about your nutrition.',
                'tags' => ['supplements', 'myths', 'facts', 'fitness', 'nutrition'],
                'is_featured' => false,
                'status' => 'draft',
                'published_at' => null,
                'views_count' => 0,
                'likes_count' => 0,
                'comments_count' => 0,
                'author_id' => 1,
                'category_id' => 1,
            ],
        ];

        foreach ($blogs as $blog) {
            Blog::create($blog);
        }

        // Create additional random blogs using factory
        if (User::count() > 0 && Category::count() > 0) {
            $userIds = User::pluck('id')->toArray();
            $categoryIds = Category::pluck('id')->toArray();
            
            // Create 10 published blogs
            for ($i = 0; $i < 10; $i++) {
                Blog::factory()->published()->create([
                    'author_id' => $userIds[array_rand($userIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                ]);
            }
            
            // Create 5 draft blogs
            for ($i = 0; $i < 5; $i++) {
                Blog::factory()->draft()->create([
                    'author_id' => $userIds[array_rand($userIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                ]);
            }
            
            // Create 3 featured blogs
            for ($i = 0; $i < 3; $i++) {
                Blog::factory()->featured()->create([
                    'author_id' => $userIds[array_rand($userIds)],
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                ]);
            }
        }
    }

    private function getProteinSupplementContent()
    {
        return "
            <h2>Introduction to Protein Supplements</h2>
            <p>Protein supplements have become a cornerstone of modern fitness nutrition, offering a convenient way to meet your daily protein requirements. Whether you're looking to build muscle, lose weight, or simply maintain a healthy lifestyle, understanding the different types of protein supplements available can help you make informed decisions about your nutrition.</p>
            
            <h3>Types of Protein Supplements</h3>
            <h4>Whey Protein</h4>
            <p>Whey protein is derived from milk and is considered one of the highest quality proteins available. It contains all nine essential amino acids and is quickly absorbed by the body, making it ideal for post-workout recovery.</p>
            
            <h4>Casein Protein</h4>
            <p>Also derived from milk, casein protein is absorbed more slowly than whey, providing a steady release of amino acids over several hours. This makes it perfect for nighttime consumption or between meals.</p>
            
            <h4>Plant-Based Proteins</h4>
            <p>For those following vegetarian or vegan diets, plant-based proteins offer excellent alternatives. Popular options include pea protein, hemp protein, and rice protein blends.</p>
            
            <h3>When to Take Protein Supplements</h3>
            <p>The timing of protein consumption can impact its effectiveness. Post-workout consumption within 30-60 minutes can help maximize muscle protein synthesis, while consuming protein throughout the day helps maintain positive nitrogen balance.</p>
            
            <h3>Choosing the Right Protein Supplement</h3>
            <p>When selecting a protein supplement, consider your dietary restrictions, fitness goals, and personal preferences. Look for products with minimal additives and third-party testing for quality assurance.</p>
        ";
    }

    private function getWorkoutNutritionContent()
    {
        return "
            <h2>The Importance of Workout Nutrition Timing</h2>
            <p>Proper nutrition timing around your workouts can significantly impact your performance, recovery, and results. Understanding when and what to eat before and after exercise is crucial for maximizing your fitness goals.</p>
            
            <h3>Pre-Workout Nutrition</h3>
            <p>Pre-workout nutrition should focus on providing energy for your training session while avoiding digestive discomfort. The ideal pre-workout meal or snack should be consumed 1-3 hours before exercise.</p>
            
            <h4>What to Eat Before Working Out</h4>
            <ul>
                <li>Complex carbohydrates for sustained energy</li>
                <li>Moderate protein for muscle support</li>
                <li>Minimal fat and fiber to avoid digestive issues</li>
                <li>Adequate hydration</li>
            </ul>
            
            <h3>Post-Workout Nutrition</h3>
            <p>Post-workout nutrition is crucial for recovery, muscle protein synthesis, and glycogen replenishment. The post-workout window, often called the 'anabolic window,' is when your body is most receptive to nutrients.</p>
            
            <h4>What to Eat After Working Out</h4>
            <ul>
                <li>High-quality protein for muscle recovery</li>
                <li>Fast-digesting carbohydrates to replenish glycogen</li>
                <li>Electrolytes for proper hydration</li>
                <li>Anti-inflammatory foods to reduce muscle soreness</li>
            </ul>
            
            <h3>Hydration Strategies</h3>
            <p>Proper hydration is essential both before and after workouts. Aim to drink water throughout the day and consider electrolyte replacement for intense or long-duration exercise sessions.</p>
        ";
    }

    private function getProteinSynthesisContent()
    {
        return "
            <h2>Understanding Muscle Protein Synthesis</h2>
            <p>Muscle protein synthesis (MPS) is the process by which your body builds new muscle proteins to repair and grow muscle tissue. Understanding this process is key to optimizing your muscle-building efforts.</p>
            
            <h3>The Science Behind MPS</h3>
            <p>MPS is triggered by resistance training and enhanced by adequate protein intake. The process involves the creation of new proteins that replace damaged ones and add to existing muscle mass.</p>
            
            <h4>Factors That Influence MPS</h4>
            <ul>
                <li>Resistance training intensity and volume</li>
                <li>Protein intake and timing</li>
                <li>Sleep quality and duration</li>
                <li>Hormonal factors</li>
                <li>Age and training status</li>
            </ul>
            
            <h3>Optimizing Protein Synthesis</h3>
            <p>To maximize MPS, focus on consuming adequate protein throughout the day, engaging in progressive resistance training, and getting sufficient rest for recovery.</p>
            
            <h4>Protein Requirements for MPS</h4>
            <p>Research suggests consuming 20-30 grams of high-quality protein per meal can optimally stimulate MPS. This should be distributed throughout the day for maximum benefit.</p>
            
            <h3>The Role of Amino Acids</h3>
            <p>Essential amino acids, particularly leucine, play a crucial role in triggering MPS. Ensuring adequate intake of all essential amino acids is vital for optimal muscle building.</p>
        ";
    }

    private function getPlantProteinContent()
    {
        return "
            <h2>Plant-Based Protein for Vegetarian Athletes</h2>
            <p>Vegetarian and vegan athletes can absolutely meet their protein needs through plant-based sources. With proper planning and knowledge, plant proteins can support muscle building and athletic performance just as effectively as animal proteins.</p>
            
            <h3>Complete vs. Incomplete Proteins</h3>
            <p>While most plant proteins are considered 'incomplete' because they lack one or more essential amino acids, combining different plant protein sources can create complete amino acid profiles.</p>
            
            <h4>Top Plant Protein Sources</h4>
            <ul>
                <li>Legumes (beans, lentils, chickpeas)</li>
                <li>Quinoa (complete protein)</li>
                <li>Hemp seeds and protein powder</li>
                <li>Chia seeds</li>
                <li>Spirulina</li>
                <li>Nutritional yeast</li>
                <li>Tempeh and tofu</li>
            </ul>
            
            <h3>Protein Combining Strategies</h3>
            <p>Traditional food combinations like rice and beans, or hummus and whole grain pita, naturally provide complete amino acid profiles. You don't need to combine proteins at every meal, but aim for variety throughout the day.</p>
            
            <h4>Plant-Based Protein Supplements</h4>
            <p>For convenience and to ensure adequate protein intake, consider plant-based protein powders made from pea, hemp, rice, or mixed plant sources.</p>
            
            <h3>Maximizing Plant Protein Absorption</h3>
            <p>To enhance the absorption of plant proteins, pair them with vitamin C-rich foods, avoid excessive fiber at the same meal, and consider digestive enzymes if needed.</p>
        ";
    }

    private function getSupplementMythsContent()
    {
        return "
            <h2>Common Supplement Myths Debunked</h2>
            <p>The supplement industry is filled with misinformation and marketing hype. Let's separate fact from fiction by examining some of the most common myths about fitness supplements.</p>
            
            <h3>Myth 1: More Protein is Always Better</h3>
            <p><strong>Truth:</strong> While protein is important for muscle building, consuming excessive amounts won't lead to more muscle growth. Your body can only utilize a certain amount of protein at one time.</p>
            
            <h3>Myth 2: Supplements Are Necessary for Results</h3>
            <p><strong>Truth:</strong> While supplements can be helpful, they're not necessary for achieving fitness goals. A well-balanced diet can provide all the nutrients needed for most people.</p>
            
            <h3>Myth 3: Natural Means Safe</h3>
            <p><strong>Truth:</strong> Natural doesn't automatically mean safe. Many natural substances can be harmful in large doses or when combined with certain medications.</p>
            
            <h3>Myth 4: All Protein Powders Are the Same</h3>
            <p><strong>Truth:</strong> Different protein sources have varying amino acid profiles, absorption rates, and quality. Choose based on your specific needs and dietary restrictions.</p>
            
            <h3>Myth 5: You Need to Take Supplements Immediately After Working Out</h3>
            <p><strong>Truth:</strong> While post-workout nutrition is important, the 'anabolic window' is much longer than previously thought. Focus on overall daily nutrition rather than precise timing.</p>
            
            <h3>Making Informed Supplement Choices</h3>
            <p>When considering supplements, research the science, consult with healthcare professionals, and choose products from reputable companies with third-party testing.</p>
        ";
    }
}
