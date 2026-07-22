import os
import glob

files = glob.glob('views/auth/*.php')

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    # Fix canvas width/height
    content = content.replace(
        '<canvas id="authParticlesCanvas" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:0"></canvas>',
        '<canvas id="authParticlesCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>'
    )
    
    # Fix particle initialization
    target2 = """    var numParticles = 80;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * 800,
        y: (Math.random() - 0.5) * 500,
        z: (Math.random() - 0.5) * 400,"""
        
    repl2 = """    var numParticles = 80;
    var initLimitX = Math.max(450, w * 0.6);
    var initLimitY = Math.max(300, h * 0.6);
    var initLimitZ = 200;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * initLimitX * 2,
        y: (Math.random() - 0.5) * initLimitY * 2,
        z: (Math.random() - 0.5) * initLimitZ * 2,"""
        
    content = content.replace(target2, repl2)
    
    # Fix particle boundaries
    target3 = """      var projected = [];
      particles.forEach(function(p) {
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        if (Math.abs(p.x) > 450) p.vx *= -1;
        if (Math.abs(p.y) > 300) p.vy *= -1;
        if (Math.abs(p.z) > 200) p.vz *= -1;"""
        
    repl3 = """      var projected = [];
      var limitX = Math.max(450, w * 0.6);
      var limitY = Math.max(300, h * 0.6);
      var limitZ = 200;
      particles.forEach(function(p) {
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        if (Math.abs(p.x) > limitX) p.vx *= -1;
        if (Math.abs(p.y) > limitY) p.vy *= -1;
        if (Math.abs(p.z) > limitZ) p.vz *= -1;"""
        
    content = content.replace(target3, repl3)
    
    with open(filepath, 'w') as f:
        f.write(content)

print("Done fixing canvas sizes in auth pages.")
