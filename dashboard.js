document.addEventListener('DOMContentLoaded', function() {
    const avgWeight = document.getElementById('avgWeight').textContent;
    const avgSteps = document.getElementById('avgSteps').textContent;
    const avgCalories = document.getElementById('avgCalories').textContent;
    const avgSleep = document.getElementById('avgSleep').textContent;

    console.log(`Average Weight: ${avgWeight} kg`);
    console.log(`Average Steps: ${avgSteps}`);
    console.log(`Average Calories: ${avgCalories}`);
    console.log(`Average Sleep Hours: ${avgSleep}`);
});
